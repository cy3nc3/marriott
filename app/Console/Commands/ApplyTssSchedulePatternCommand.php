<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\ClassSchedule;
use App\Models\Section;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\TeacherSubject;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ApplyTssSchedulePatternCommand extends Command
{
    protected $signature = 'demo:apply-tss-schedule-pattern
        {--file=TSS Schedule SY 25-26.xlsx}
        {--dry-run}';

    protected $description = 'Apply the TSS Schedule SY 25-26 workbook pattern to seeded section schedules.';

    private const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    public function handle(): int
    {
        $file = base_path((string) $this->option('file'));
        $dryRun = (bool) $this->option('dry-run');

        if (! is_file($file)) {
            $this->components->error("TSS schedule workbook not found: {$file}");

            return self::FAILURE;
        }

        $patterns = $this->extractPatterns($file);
        if ($patterns === []) {
            $this->components->error('No academic schedule patterns were extracted from the workbook.');

            return self::FAILURE;
        }
        $sectionPatterns = $this->flattenSectionPatterns($patterns);

        $academicRowsDeleted = 0;
        $scheduleRowsCreated = 0;
        $assignmentsCreated = 0;

        DB::transaction(function () use (
            $sectionPatterns,
            $dryRun,
            &$academicRowsDeleted,
            &$scheduleRowsCreated,
            &$assignmentsCreated
        ): void {
            $sections = Section::query()
                ->with(['academicYear:id,name', 'gradeLevel:id,level_order,name'])
                ->orderBy('academic_year_id')
                ->orderBy('grade_level_id')
                ->orderBy('id')
                ->get();

            foreach ($sections as $section) {
                $grade = (int) ($section->gradeLevel?->level_order ?? 0);
                if (! in_array($grade, [7, 8, 9, 10], true)) {
                    continue;
                }

                $sectionPatternKey = $this->patternKeyForSection($section, $sectionPatterns);
                $sectionPattern = $sectionPatterns[$sectionPatternKey] ?? null;
                if (! is_array($sectionPattern)) {
                    continue;
                }

                $assignmentIds = SubjectAssignment::query()
                    ->where('section_id', $section->id)
                    ->pluck('id');

                $deleteCount = ClassSchedule::query()
                    ->where('section_id', $section->id)
                    ->whereIn('subject_assignment_id', $assignmentIds)
                    ->count();
                $academicRowsDeleted += $deleteCount;

                if (! $dryRun) {
                    ClassSchedule::query()
                        ->where('section_id', $section->id)
                        ->whereIn('subject_assignment_id', $assignmentIds)
                        ->delete();
                }

                foreach ($sectionPattern as $subjectTag => $slot) {
                    $subject = Subject::query()
                        ->where('grade_level_id', $section->grade_level_id)
                        ->where('subject_code', 'like', "{$subjectTag}%")
                        ->orderBy('subject_code')
                        ->first();

                    if (! $subject instanceof Subject) {
                        continue;
                    }

                    $assignment = $this->assignmentForSectionSubject($section, $subject, $subjectTag, $assignmentsCreated, $dryRun);
                    if (! $assignment instanceof SubjectAssignment) {
                        continue;
                    }

                    foreach (self::DAYS as $day) {
                        $scheduleRowsCreated++;

                        if ($dryRun) {
                            continue;
                        }

                        ClassSchedule::query()->create([
                            'section_id' => $section->id,
                            'subject_assignment_id' => $assignment->id,
                            'type' => 'academic',
                            'label' => null,
                            'day' => $day,
                            'start_time' => $slot['start_time'],
                            'end_time' => $slot['end_time'],
                        ]);
                    }
                }
            }
        });

        $this->components->info("Academic schedule rows removed: {$academicRowsDeleted}");
        $this->components->info("Academic schedule rows created: {$scheduleRowsCreated}");
        $this->components->info("Subject assignments created: {$assignmentsCreated}");

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, array<string, array{start_time: string, end_time: string}>>>
     */
    private function extractPatterns(string $file): array
    {
        $reader = IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file);
        $candidates = [];

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $highestRow = $sheet->getHighestRow();
            $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestColumn());

            for ($row = 1; $row <= $highestRow; $row++) {
                $time = trim((string) $sheet->getCell("A{$row}")->getFormattedValue());
                if (! str_contains($time, '-')) {
                    continue;
                }

                $slot = $this->parseTimeSlot($time);
                if ($slot === null) {
                    continue;
                }

                for ($column = 3; $column <= min(7, $highestColumn); $column++) {
                    $cell = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($column).$row)->getFormattedValue());
                    $entry = $this->parseAcademicEntry($cell);
                    if ($entry === null) {
                        continue;
                    }

                    $key = implode('|', [$entry['grade'], $entry['section_key'], $entry['subject_tag'], $slot['start_time'], $slot['end_time']]);
                    $candidates[$entry['grade']][$entry['section_key']][$entry['subject_tag']][$key] ??= [
                        'start_time' => $slot['start_time'],
                        'end_time' => $slot['end_time'],
                        'count' => 0,
                    ];
                    $candidates[$entry['grade']][$entry['section_key']][$entry['subject_tag']][$key]['count']++;
                }
            }
        }

        $patterns = [];
        foreach ($candidates as $grade => $sectionRows) {
            foreach ($sectionRows as $sectionKey => $subjectRows) {
                foreach ($subjectRows as $subjectTag => $slots) {
                    usort($slots, fn (array $a, array $b): int => $b['count'] <=> $a['count']);
                $patterns[$grade][$sectionKey][$subjectTag] = [
                    'start_time' => $slots[0]['start_time'],
                    'end_time' => $this->addMinutes($slots[0]['start_time'], 60),
                ];
                }
            }
        }

        return $patterns;
    }

    /**
     * @param  array<int, array<string, array<string, array{start_time: string, end_time: string}>>>  $patterns
     * @return array<string, array<string, array{start_time: string, end_time: string}>>
     */
    private function flattenSectionPatterns(array $patterns): array
    {
        $orderedKeys = ['stpaul', 'stanthony', 'stfrancis', 'stanne', 'stjohn'];
        $flattened = [];

        foreach ($orderedKeys as $key) {
            foreach ($patterns as $gradePatterns) {
                if (isset($gradePatterns[$key])) {
                    $flattened[$key] = $gradePatterns[$key];
                    break;
                }
            }
        }

        return $flattened;
    }

    /**
     * @return array{subject_tag: string, grade: int, section_key: string}|null
     */
    private function parseAcademicEntry(string $value): ?array
    {
        $normalized = preg_replace('/\s+/', ' ', trim($value));
        if (! is_string($normalized) || $normalized === '') {
            return null;
        }

        if (preg_match('/vacant|supervised|library|lunch|recess|enrichment/i', $normalized)) {
            return null;
        }

        if (! preg_match('/^(.*?)\s*(7|8|9|10)\s*[- ]\s*(.+)$/i', $normalized, $matches)) {
            return null;
        }

        $subjectTag = $this->subjectTagFromWorkbookLabel($matches[1]);
        $sectionKey = $this->normalizeSectionKey($matches[3]);

        if ($subjectTag === '' || $sectionKey === '') {
            return null;
        }

        return [
            'subject_tag' => $subjectTag,
            'grade' => (int) $matches[2],
            'section_key' => $sectionKey,
        ];
    }

    private function subjectTagFromWorkbookLabel(string $label): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Z.]/i', '', $label) ?? '');

        return match (true) {
            str_contains($normalized, 'APAN'), str_contains($normalized, 'A.PAN') => 'AP',
            str_contains($normalized, 'ENGLISH') => 'ENG',
            str_contains($normalized, 'FILIPINO') => 'FIL',
            str_contains($normalized, 'MAPEH') => 'MAPEH',
            str_contains($normalized, 'MATH') => 'MATH',
            str_contains($normalized, 'SCIENCE') => 'SCI',
            str_contains($normalized, 'TLE') => 'TLE',
            $normalized === 'CL' => 'ESP',
            default => '',
        };
    }

    /**
     * @return array{start_time: string, end_time: string}|null
     */
    private function parseTimeSlot(string $value): ?array
    {
        if (! preg_match('/^(\d{1,2}):(\d{2})\s*-\s*(\d{1,2}):(\d{2})$/', trim($value), $matches)) {
            return null;
        }

        $start = $this->normalizeSchoolTime((int) $matches[1], (int) $matches[2]);
        $end = $this->normalizeSchoolTime((int) $matches[3], (int) $matches[4]);

        return [
            'start_time' => $start,
            'end_time' => $end,
        ];
    }

    private function normalizeSchoolTime(int $hour, int $minute): string
    {
        if ($hour >= 1 && $hour <= 5) {
            $hour += 12;
        }

        return sprintf('%02d:%02d:00', $hour, $minute);
    }

    private function addMinutes(string $time, int $minutes): string
    {
        [$hour, $minute] = array_map('intval', explode(':', substr($time, 0, 5)));
        $totalMinutes = ($hour * 60) + $minute + $minutes;

        return sprintf('%02d:%02d:00', intdiv($totalMinutes, 60), $totalMinutes % 60);
    }

    /**
     * @param  array<string, mixed>  $patterns
     */
    private function patternKeyForSection(Section $section, array $patterns): string
    {
        $keys = array_values(array_intersect(['stpaul', 'stanthony', 'stfrancis', 'stanne', 'stjohn'], array_keys($patterns)));

        if ($keys === []) {
            return $this->normalizeSectionKey((string) $section->name);
        }

        return $keys[((int) $section->id) % count($keys)];
    }

    private function normalizeSectionKey(string $section): string
    {
        $key = strtolower(preg_replace('/[^a-z0-9]+/i', '', $section) ?? '');

        return match ($key) {
            'paul', 'stpaul', 'saintpaul' => 'stpaul',
            'anthony', 'stanthony', 'saintanthony' => 'stanthony',
            'francis', 'stfrancis', 'saintfrancis' => 'stfrancis',
            'anne', 'stanne', 'saintanne' => 'stanne',
            'john', 'stjohn', 'saintjohn' => 'stjohn',
            default => $key,
        };
    }

    private function assignmentForSectionSubject(
        Section $section,
        Subject $subject,
        string $subjectTag,
        int &$assignmentsCreated,
        bool $dryRun
    ): ?SubjectAssignment {
        $assignment = SubjectAssignment::query()
            ->where('section_id', $section->id)
            ->whereHas('teacherSubject', fn ($query) => $query->where('subject_id', $subject->id))
            ->first();

        if ($assignment instanceof SubjectAssignment || $dryRun) {
            return $assignment;
        }

        $teacher = $this->firstQualifiedTeacherForTag($subjectTag);
        if (! $teacher instanceof User) {
            return null;
        }

        $teacherSubject = TeacherSubject::query()->firstOrCreate(
            [
                'teacher_id' => $teacher->id,
                'subject_id' => $subject->id,
            ],
            [
                'qualification_status' => 'fully_qualified',
            ]
        );

        $assignmentsCreated++;

        return SubjectAssignment::query()->create([
            'section_id' => $section->id,
            'teacher_subject_id' => $teacherSubject->id,
        ]);
    }

    private function firstQualifiedTeacherForTag(string $subjectTag): ?User
    {
        return User::query()
            ->where('role', UserRole::TEACHER)
            ->where('is_active', true)
            ->whereHas('teacherProfile', function ($query) use ($subjectTag): void {
                $query->where('qualification_status', 'fully_qualified')
                    ->whereJsonContains('subject_competency_tags', $subjectTag);
            })
            ->orderByRaw("case when email like 'demo.%' then 1 else 0 end")
            ->orderBy('id')
            ->first();
    }

}
