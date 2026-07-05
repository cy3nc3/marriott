<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\ClassSchedule;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\TeacherProfile;
use App\Models\TeacherSubject;
use App\Models\User;
use Database\Seeders\Support\SeedNameBank;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RepairDemoTeacherScheduleAssignmentsCommand extends Command
{
    protected $signature = 'demo:repair-teacher-schedules {--dry-run}';

    protected $description = 'Repair demo subject assignments so seeded schedules use subject-qualified teachers.';

    private const SUBJECT_LABELS = [
        'AP' => ['major' => 'Araling Panlipunan', 'degree' => 'Bachelor of Secondary Education major in Social Studies'],
        'ENG' => ['major' => 'English', 'degree' => 'Bachelor of Secondary Education major in English'],
        'ESP' => ['major' => 'Values Education', 'degree' => 'Bachelor of Secondary Education major in Values Education'],
        'FIL' => ['major' => 'Filipino', 'degree' => 'Bachelor of Secondary Education major in Filipino'],
        'MAPEH' => ['major' => 'MAPEH', 'degree' => 'Bachelor of Physical Education'],
        'MATH' => ['major' => 'Mathematics', 'degree' => 'Bachelor of Secondary Education major in Mathematics'],
        'SCI' => ['major' => 'Science', 'degree' => 'Bachelor of Secondary Education major in Science'],
        'TLE' => ['major' => 'Technology and Livelihood Education', 'degree' => 'Bachelor of Technical-Vocational Teacher Education'],
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updatedAssignments = 0;
        $createdTeachers = 0;
        $createdTeacherSubjects = 0;
        $deletedTeacherSubjects = 0;
        $deletedDuplicateSchedules = 0;
        $taggedOriginalProfiles = 0;
        $assignedToOriginalTeachers = 0;
        $assignedToDemoTeachers = 0;
        $teacherCache = [];
        $teacherSubjectCache = [];
        $teacherBusySlots = [];
        $teacherLoadCounts = [];
        $hashedPassword = Hash::make('password');

        DB::transaction(function () use (
            $dryRun,
            $hashedPassword,
            &$updatedAssignments,
            &$createdTeachers,
            &$createdTeacherSubjects,
            &$deletedTeacherSubjects,
            &$deletedDuplicateSchedules,
            &$taggedOriginalProfiles,
            &$assignedToOriginalTeachers,
            &$assignedToDemoTeachers,
            &$teacherCache,
            &$teacherSubjectCache,
            &$teacherBusySlots,
            &$teacherLoadCounts
        ): void {
            $taggedOriginalProfiles = $this->backfillOriginalTeacherProfileTags($dryRun);
            $originalTeachersByTag = $this->originalTeachersByTag();

            $assignments = SubjectAssignment::query()
                ->with([
                    'section.academicYear:id,name',
                    'section.gradeLevel:id,name,level_order',
                    'teacherSubject.subject:id,grade_level_id,subject_code,subject_name',
                    'teacherSubject.subject.gradeLevel:id,level_order',
                ])
            ->orderBy('section_id')
            ->orderBy('id')
            ->get();

            foreach ($assignments as $assignment) {
                $subject = $assignment->teacherSubject?->subject;
                if (! $subject instanceof Subject || ! $assignment->section) {
                    continue;
                }

                $subjectTag = $this->subjectTag($subject);
                if ($subjectTag === '') {
                    continue;
                }

                $scheduleSlots = $this->scheduleSlotsForAssignment($assignment);
                $teacher = $this->qualifiedOriginalTeacherForAssignment(
                    $subjectTag,
                    $scheduleSlots,
                    $originalTeachersByTag,
                    $teacherBusySlots,
                    $teacherLoadCounts
                );

                if ($teacher instanceof User) {
                    $assignedToOriginalTeachers++;
                }

                if (! $teacher instanceof User) {
                    $assignedToDemoTeachers++;
                    $teacher = $this->qualifiedDemoTeacherForAssignment(
                        $assignment,
                        $subjectTag,
                        $teacherCache,
                        $createdTeachers,
                        $dryRun,
                        $hashedPassword
                    );
                }

                if (! $teacher instanceof User) {
                    continue;
                }

                $teacherSubject = $this->teacherSubjectFor(
                    $teacher,
                    $subject,
                    $teacherSubjectCache,
                    $createdTeacherSubjects,
                    $dryRun
                );

                if (! $teacherSubject instanceof TeacherSubject) {
                    continue;
                }

                if ($assignment->teacher_subject_id !== $teacherSubject->id) {
                    $updatedAssignments++;

                    if (! $dryRun) {
                        $assignment->forceFill(['teacher_subject_id' => $teacherSubject->id])->save();
                    }
                }

                $this->reserveTeacherSlots($teacher, $scheduleSlots, $teacherBusySlots, $teacherLoadCounts);
            }

            $deletedDuplicateSchedules = $this->deleteExactDuplicateSchedules($dryRun);
            $deletedTeacherSubjects = $this->deleteUnqualifiedOrphanTeacherSubjects($dryRun);
        });

        $this->components->info("Assignments reassigned: {$updatedAssignments}");
        $this->components->info("Assignments placed on original teachers: {$assignedToOriginalTeachers}");
        $this->components->info("Assignments retained on generated demo teachers: {$assignedToDemoTeachers}");
        $this->components->info("Original teacher profiles tagged: {$taggedOriginalProfiles}");
        $this->components->info("Teachers created: {$createdTeachers}");
        $this->components->info("Teacher-subject links created: {$createdTeacherSubjects}");
        $this->components->info("Duplicate schedule rows removed: {$deletedDuplicateSchedules}");
        $this->components->info("Unqualified orphan teacher-subject links removed: {$deletedTeacherSubjects}");

        return self::SUCCESS;
    }

    /**
     * @return array<string, array<int, User>>
     */
    private function originalTeachersByTag(): array
    {
        $teachersByTag = [];

        User::query()
            ->where('role', UserRole::TEACHER)
            ->where('is_active', true)
            ->where('email', 'not like', 'demo.%')
            ->with('teacherProfile')
            ->orderBy('id')
            ->get()
            ->each(function (User $teacher) use (&$teachersByTag): void {
                $profile = $teacher->teacherProfile;
                if (! $profile || $profile->qualification_status !== 'fully_qualified') {
                    return;
                }

                $gradeBands = collect($profile->grade_band_eligibility ?? [])
                    ->map(fn (mixed $band): string => strtolower(trim((string) $band)));
                if ($gradeBands->isNotEmpty() && ! $gradeBands->contains('junior_high')) {
                    return;
                }

                collect($profile->subject_competency_tags ?? [])
                    ->map(fn (mixed $tag): string => strtoupper(trim((string) $tag)))
                    ->filter()
                    ->each(function (string $tag) use (&$teachersByTag, $teacher): void {
                        $teachersByTag[$tag] ??= [];
                        $teachersByTag[$tag][] = $teacher;
                    });
            });

        return $teachersByTag;
    }

    private function backfillOriginalTeacherProfileTags(bool $dryRun): int
    {
        $subjectTags = array_keys(self::SUBJECT_LABELS);
        $tagged = 0;

        User::query()
            ->where('role', UserRole::TEACHER)
            ->where('is_active', true)
            ->where('email', 'not like', 'demo.%')
            ->whereHas('teacherProfile', function ($query): void {
                $query->where('qualification_status', 'fully_qualified')
                    ->where(function ($nested): void {
                        $nested->whereNull('subject_competency_tags')
                            ->orWhereRaw("subject_competency_tags::text = '[]'");
                    });
            })
            ->with('teacherProfile')
            ->orderBy('id')
            ->get()
            ->each(function (User $teacher, int $index) use ($subjectTags, $dryRun, &$tagged): void {
                $subjectTag = $subjectTags[$index % count($subjectTags)];
                $labels = self::SUBJECT_LABELS[$subjectTag];
                $tagged++;

                if ($dryRun) {
                    return;
                }

                $teacher->teacherProfile?->forceFill([
                    'degree' => $teacher->teacherProfile?->degree ?: $labels['degree'],
                    'major' => $teacher->teacherProfile?->major ?: $labels['major'],
                    'professional_education_units' => $teacher->teacherProfile?->professional_education_units ?: 18,
                    'grade_band_eligibility' => ['junior_high'],
                    'subject_competency_tags' => [$subjectTag],
                ])->save();
            });

        return $tagged;
    }

    /**
     * @param  array<string, array<int, User>>  $originalTeachersByTag
     * @param  array<int, array<int, array{academic_year_id: int, day: string, start_minute: int, end_minute: int}>>  $teacherBusySlots
     * @param  array<string, int>  $teacherLoadCounts
     * @param  array<int, array{academic_year_id: int, day: string, start_minute: int, end_minute: int}>  $scheduleSlots
     */
    private function qualifiedOriginalTeacherForAssignment(
        string $subjectTag,
        array $scheduleSlots,
        array $originalTeachersByTag,
        array $teacherBusySlots,
        array $teacherLoadCounts
    ): ?User {
        $academicYearId = $this->academicYearIdFromSlots($scheduleSlots);
        $candidates = collect($originalTeachersByTag[$subjectTag] ?? [])
            ->filter(function (User $teacher) use ($teacherLoadCounts, $academicYearId): bool {
                return ($teacherLoadCounts[$this->loadKey($teacher->id, $academicYearId)] ?? 0) < 5;
            })
            ->sortByDesc(fn (User $teacher): int => $teacherLoadCounts[$this->loadKey($teacher->id, $academicYearId)] ?? 0)
            ->values();

        foreach ($candidates as $teacher) {
            $busySlots = $teacherBusySlots[$teacher->id] ?? [];
            $hasConflict = collect($scheduleSlots)->contains(
                fn (array $slot): bool => $this->overlapsAnyBusySlot($slot, $busySlots)
            );

            if (! $hasConflict) {
                return $teacher;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{academic_year_id: int, day: string, start_minute: int, end_minute: int}>
     */
    private function scheduleSlotsForAssignment(SubjectAssignment $assignment): array
    {
        return ClassSchedule::query()
            ->join('sections', 'sections.id', '=', 'class_schedules.section_id')
            ->where('subject_assignment_id', $assignment->id)
            ->orderBy('day')
            ->orderBy('start_time')
            ->get([
                'sections.academic_year_id',
                'class_schedules.day',
                'class_schedules.start_time',
                'class_schedules.end_time',
            ])
            ->map(fn (ClassSchedule $schedule): array => [
                'academic_year_id' => (int) $schedule->academic_year_id,
                'day' => (string) $schedule->day,
                'start_minute' => $this->timeToMinute((string) $schedule->start_time),
                'end_minute' => $this->timeToMinute((string) $schedule->end_time),
            ])
            ->unique(fn (array $slot): string => implode('|', $slot))
            ->values()
            ->all();
    }

    /**
     * @param  array{academic_year_id: int, day: string, start_minute: int, end_minute: int}  $slot
     * @param  array<int, array{academic_year_id: int, day: string, start_minute: int, end_minute: int}>  $busySlots
     */
    private function overlapsAnyBusySlot(array $slot, array $busySlots): bool
    {
        foreach ($busySlots as $busySlot) {
            if ($slot['academic_year_id'] !== $busySlot['academic_year_id']) {
                continue;
            }

            if ($slot['day'] !== $busySlot['day']) {
                continue;
            }

            if ($slot['start_minute'] < $busySlot['end_minute'] && $slot['end_minute'] > $busySlot['start_minute']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array{academic_year_id: int, day: string, start_minute: int, end_minute: int}>  $scheduleSlots
     * @param  array<int, array<int, array{academic_year_id: int, day: string, start_minute: int, end_minute: int}>>  $teacherBusySlots
     * @param  array<string, int>  $teacherLoadCounts
     */
    private function reserveTeacherSlots(User $teacher, array $scheduleSlots, array &$teacherBusySlots, array &$teacherLoadCounts): void
    {
        foreach ($scheduleSlots as $slot) {
            $teacherBusySlots[$teacher->id][] = $slot;
        }

        $academicYearId = $this->academicYearIdFromSlots($scheduleSlots);
        $loadKey = $this->loadKey($teacher->id, $academicYearId);
        $teacherLoadCounts[$loadKey] = ($teacherLoadCounts[$loadKey] ?? 0) + 1;
    }

    private function timeToMinute(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', substr($time, 0, 5)));

        return ($hour * 60) + $minute;
    }

    /**
     * @param  array<int, array{academic_year_id: int, day: string, start_minute: int, end_minute: int}>  $scheduleSlots
     */
    private function academicYearIdFromSlots(array $scheduleSlots): int
    {
        return (int) ($scheduleSlots[0]['academic_year_id'] ?? 0);
    }

    private function loadKey(int $teacherId, int $academicYearId): string
    {
        return "{$teacherId}:{$academicYearId}";
    }

    /**
     * @param  array<string, TeacherSubject>  $teacherSubjectCache
     */
    private function teacherSubjectFor(
        User $teacher,
        Subject $subject,
        array &$teacherSubjectCache,
        int &$createdTeacherSubjects,
        bool $dryRun
    ): ?TeacherSubject {
        $cacheKey = "{$teacher->id}:{$subject->id}";

        if (isset($teacherSubjectCache[$cacheKey])) {
            return $teacherSubjectCache[$cacheKey];
        }

        $teacherSubject = TeacherSubject::query()
            ->where('teacher_id', $teacher->id)
            ->where('subject_id', $subject->id)
            ->first();

        if (! $teacherSubject instanceof TeacherSubject) {
            $createdTeacherSubjects++;

            $teacherSubject = $dryRun
                ? new TeacherSubject([
                    'teacher_id' => $teacher->id,
                    'subject_id' => $subject->id,
                    'qualification_status' => 'fully_qualified',
                ])
                : TeacherSubject::query()->create([
                    'teacher_id' => $teacher->id,
                    'subject_id' => $subject->id,
                    'qualification_status' => 'fully_qualified',
                ]);
        } elseif ((string) $teacherSubject->qualification_status !== 'fully_qualified' && ! $dryRun) {
            $teacherSubject->forceFill(['qualification_status' => 'fully_qualified'])->save();
        }

        $teacherSubjectCache[$cacheKey] = $teacherSubject;

        return $teacherSubject;
    }

    /**
     * @param  array<string, User>  $teacherCache
     */
    private function qualifiedDemoTeacherForAssignment(
        SubjectAssignment $assignment,
        string $subjectTag,
        array &$teacherCache,
        int &$createdTeachers,
        bool $dryRun,
        string $hashedPassword
    ): ?User {
        $section = $assignment->section;
        $cacheKey = "{$section->id}:{$subjectTag}";

        if (isset($teacherCache[$cacheKey])) {
            return $teacherCache[$cacheKey];
        }

        $email = $this->demoTeacherEmail((int) $section->id, $subjectTag);
        $teacher = User::query()->where('email', $email)->first();

        if (! $teacher instanceof User) {
            $identity = SeedNameBank::teacherIdentity(((int) $section->id * 97) + strlen($subjectTag));
            $createdTeachers++;

            if ($dryRun) {
                return null;
            }

            $teacher = User::query()->create([
                'first_name' => $identity['first_name'],
                'last_name' => $identity['last_name'],
                'name' => "{$identity['first_name']} {$identity['last_name']}",
                'email' => $email,
                'personal_email' => $this->demoPersonalEmail($identity['first_name'], $identity['last_name'], (int) $section->id, $subjectTag),
                'password' => $hashedPassword,
                'birthday' => '1980-01-01',
                'role' => UserRole::TEACHER,
                'is_active' => true,
            ]);
        }

        if (! $dryRun) {
            $labels = self::SUBJECT_LABELS[$subjectTag] ?? ['major' => $subjectTag, 'degree' => 'Bachelor of Secondary Education'];

            TeacherProfile::query()->updateOrCreate(
                ['user_id' => $teacher->id],
                [
                    'qualification_status' => 'fully_qualified',
                    'is_let_passer' => true,
                    'prc_license_no' => 'PRC-DEMO-'.str_pad((string) $teacher->id, 6, '0', STR_PAD_LEFT),
                    'license_valid_until' => '2029-12-31',
                    'degree' => $labels['degree'],
                    'major' => $labels['major'],
                    'professional_education_units' => 18,
                    'grade_band_eligibility' => ['junior_high'],
                    'subject_competency_tags' => [$subjectTag],
                    'notes' => "Demo schedule repair teacher for {$subjectTag}.",
                ]
            );
        }

        $teacherCache[$cacheKey] = $teacher;

        return $teacher;
    }

    private function deleteExactDuplicateSchedules(bool $dryRun): int
    {
        $duplicateIds = ClassSchedule::query()
            ->selectRaw('MIN(id) as keep_id, ARRAY_AGG(id) as ids')
            ->groupBy('section_id', 'subject_assignment_id', 'day', 'start_time', 'end_time')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->flatMap(function ($row): array {
                $ids = is_array($row->ids)
                    ? $row->ids
                    : explode(',', trim((string) $row->ids, '{}'));

                return collect($ids)
                    ->map(fn (mixed $id): int => (int) $id)
                    ->filter(fn (int $id): bool => $id > 0 && $id !== (int) $row->keep_id)
                    ->values()
                    ->all();
            })
            ->values();

        if (! $dryRun && $duplicateIds->isNotEmpty()) {
            ClassSchedule::query()->whereIn('id', $duplicateIds->all())->delete();
        }

        return $duplicateIds->count();
    }

    private function deleteUnqualifiedOrphanTeacherSubjects(bool $dryRun): int
    {
        $orphanTeacherSubjects = TeacherSubject::query()
            ->with(['teacher.teacherProfile', 'subject:id,subject_code'])
            ->whereDoesntHave('subjectAssignments')
            ->get()
            ->filter(function (TeacherSubject $teacherSubject): bool {
                $subject = $teacherSubject->subject;
                $profile = $teacherSubject->teacher?->teacherProfile;
                if (! $subject || ! $profile) {
                    return false;
                }

                $subjectTag = $this->subjectTag($subject);
                $tags = collect($profile->subject_competency_tags ?? [])
                    ->map(fn (mixed $tag): string => strtoupper(trim((string) $tag)))
                    ->filter();

                return $subjectTag !== '' && ! $tags->contains($subjectTag);
            })
            ->pluck('id')
            ->values();

        if (! $dryRun && $orphanTeacherSubjects->isNotEmpty()) {
            TeacherSubject::query()->whereIn('id', $orphanTeacherSubjects->all())->delete();
        }

        return $orphanTeacherSubjects->count();
    }

    private function subjectTag(Subject $subject): string
    {
        return strtoupper((string) preg_replace('/\d+$/', '', (string) $subject->subject_code));
    }

    private function demoTeacherEmail(int $sectionId, string $subjectTag): string
    {
        return 'demo.'.Str::lower($subjectTag).".section{$sectionId}@marriott.edu";
    }

    private function demoPersonalEmail(string $firstName, string $lastName, int $sectionId, string $subjectTag): string
    {
        $firstToken = Str::of($firstName)->ascii()->lower()->replaceMatches('/[^a-z0-9 ]+/', '')->squish()->explode(' ')->filter()->first() ?: 'teacher';
        $lastToken = Str::of($lastName)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '') ?: 'demo';

        return "{$firstToken}.{$lastToken}.{$subjectTag}{$sectionId}@test.com";
    }
}
