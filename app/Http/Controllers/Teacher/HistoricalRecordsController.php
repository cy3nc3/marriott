<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teacher\Concerns\ResolvesTeacherAcademicYearAccess;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\StudentScore;
use App\Models\SubjectAssignment;
use Inertia\Inertia;
use Inertia\Response;

class HistoricalRecordsController extends Controller
{
    use ResolvesTeacherAcademicYearAccess;

    public function index(): Response
    {
        $teacherId = (int) auth()->id();
        $requestedYearId = request()->integer('academic_year_id') ?: null;
        $requestedGradeLevelId = request()->integer('grade_level_id') ?: null;
        $requestedSectionId = request()->integer('section_id') ?: null;
        $requestedAssignmentId = request()->integer('subject_assignment_id') ?: null;

        $academicYearOptions = $this->resolveTeacherAcademicYearOptions($teacherId)
            ->filter(fn (array $year): bool => $year['status'] !== 'ongoing')
            ->values();
        $selectedAcademicYearId = $requestedYearId;
        if (! $selectedAcademicYearId && $academicYearOptions->isNotEmpty()) {
            $selectedAcademicYearId = (int) $academicYearOptions->first()['id'];
        }
        if ($selectedAcademicYearId && ! $academicYearOptions->pluck('id')->contains($selectedAcademicYearId)) {
            abort(403);
        }

        $assignmentPool = SubjectAssignment::query()
            ->with([
                'section:id,academic_year_id,grade_level_id,name',
                'section.gradeLevel:id,name,level_order',
                'teacherSubject:id,teacher_id,subject_id',
                'teacherSubject.subject:id,subject_name',
            ])
            ->whereHas('teacherSubject', function ($query) use ($teacherId): void {
                $query->where('teacher_id', $teacherId);
            })
            ->when($selectedAcademicYearId, function ($query) use ($selectedAcademicYearId): void {
                $query->whereHas('section', function ($sectionQuery) use ($selectedAcademicYearId): void {
                    $sectionQuery->where('academic_year_id', $selectedAcademicYearId);
                });
            })
            ->orderBy('id')
            ->get();

        $assignmentIds = $assignmentPool
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values();

        $recordedAssignmentIds = collect();
        if ($assignmentIds->isNotEmpty()) {
            $recordedAssignmentIds = FinalGrade::query()
                ->whereIn('subject_assignment_id', $assignmentIds->all())
                ->pluck('subject_assignment_id')
                ->merge(
                    Attendance::query()
                        ->whereIn('subject_assignment_id', $assignmentIds->all())
                        ->pluck('subject_assignment_id')
                )
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values();
        }

        $assignmentPoolForOptions = $recordedAssignmentIds->isNotEmpty()
            ? $assignmentPool->filter(
                fn (SubjectAssignment $assignment): bool => $recordedAssignmentIds->contains((int) $assignment->id)
            )->values()
            : $assignmentPool;

        $gradeLevelOptions = $assignmentPoolForOptions
            ->map(function (SubjectAssignment $assignment): ?array {
                $gradeLevel = $assignment->section?->gradeLevel;
                if (! $gradeLevel) {
                    return null;
                }

                return [
                    'id' => (int) $gradeLevel->id,
                    'name' => (string) $gradeLevel->name,
                    'level_order' => (int) ($gradeLevel->level_order ?? 0),
                ];
            })
            ->filter()
            ->unique('id')
            ->sortBy('level_order')
            ->values();

        $selectedGradeLevelId = $requestedGradeLevelId
            ?: ($gradeLevelOptions->first()['id'] ?? null);
        if ($selectedGradeLevelId && ! $gradeLevelOptions->pluck('id')->contains($selectedGradeLevelId)) {
            $selectedGradeLevelId = $gradeLevelOptions->first()['id'] ?? null;
        }

        $sectionOptions = $assignmentPoolForOptions
            ->filter(function (SubjectAssignment $assignment) use ($selectedGradeLevelId): bool {
                return $selectedGradeLevelId
                    ? (int) $assignment->section?->grade_level_id === (int) $selectedGradeLevelId
                    : true;
            })
            ->map(function (SubjectAssignment $assignment): ?array {
                $section = $assignment->section;
                if (! $section) {
                    return null;
                }

                return [
                    'id' => (int) $section->id,
                    'name' => (string) $section->name,
                ];
            })
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $selectedSectionId = $requestedSectionId ?: ($sectionOptions->first()['id'] ?? null);
        if ($selectedSectionId && ! $sectionOptions->pluck('id')->contains($selectedSectionId)) {
            $selectedSectionId = $sectionOptions->first()['id'] ?? null;
        }

        $subjectOptions = $assignmentPoolForOptions
            ->filter(function (SubjectAssignment $assignment) use ($selectedSectionId): bool {
                return $selectedSectionId
                    ? (int) $assignment->section_id === (int) $selectedSectionId
                    : true;
            })
            ->map(function (SubjectAssignment $assignment): ?array {
                $subject = $assignment->teacherSubject?->subject;
                if (! $subject) {
                    return null;
                }

                return [
                    'subject_assignment_id' => (int) $assignment->id,
                    'subject_name' => (string) $subject->subject_name,
                ];
            })
            ->filter()
            ->sortBy('subject_name')
            ->values();

        $selectedAssignmentId = $requestedAssignmentId
            ?: ($subjectOptions->first()['subject_assignment_id'] ?? null);
        if ($selectedAssignmentId && ! $subjectOptions->pluck('subject_assignment_id')->contains($selectedAssignmentId)) {
            $selectedAssignmentId = $subjectOptions->first()['subject_assignment_id'] ?? null;
        }

        $selectedAssignment = $selectedAssignmentId
            ? $assignmentPoolForOptions->firstWhere('id', (int) $selectedAssignmentId)
            : null;

        $students = collect();
        $recordsByEnrollment = [];

        if ($selectedAssignment instanceof SubjectAssignment) {
            $recordedEnrollmentIds = FinalGrade::query()
                ->where('subject_assignment_id', $selectedAssignment->id)
                ->pluck('enrollment_id')
                ->map(fn ($id): int => (int) $id)
                ->merge(
                    Attendance::query()
                        ->where('subject_assignment_id', $selectedAssignment->id)
                        ->pluck('enrollment_id')
                        ->map(fn ($id): int => (int) $id)
                )
                ->unique()
                ->values();

            $students = Enrollment::query()
                ->with(['student:id,lrn,first_name,last_name'])
                ->where('academic_year_id', $selectedAssignment->section?->academic_year_id)
                ->where('section_id', $selectedAssignment->section_id)
                ->where('status', 'enrolled')
                ->whereIn('id', $recordedEnrollmentIds->all())
                ->orderBy('id')
                ->get(['id', 'student_id'])
                ->map(function (Enrollment $enrollment): array {
                    return [
                        'enrollment_id' => (int) $enrollment->id,
                        'student_name' => trim((string) ($enrollment->student?->last_name.', '.$enrollment->student?->first_name)),
                        'lrn' => (string) ($enrollment->student?->lrn ?? ''),
                    ];
                })
                ->values();

            $enrollmentIds = $students->pluck('enrollment_id')->all();

            if ($enrollmentIds !== []) {
                $gradeRows = FinalGrade::query()
                    ->whereIn('enrollment_id', $enrollmentIds)
                    ->where('subject_assignment_id', $selectedAssignment->id)
                    ->orderByRaw("CASE quarter WHEN '1' THEN 1 WHEN '2' THEN 2 WHEN '3' THEN 3 WHEN '4' THEN 4 WHEN 'final' THEN 5 ELSE 6 END")
                    ->get(['enrollment_id', 'quarter', 'grade', 'is_locked'])
                    ->groupBy('enrollment_id');

                $attendanceRows = Attendance::query()
                    ->whereIn('enrollment_id', $enrollmentIds)
                    ->where('subject_assignment_id', $selectedAssignment->id)
                    ->orderBy('date')
                    ->get(['enrollment_id', 'date', 'status'])
                    ->groupBy('enrollment_id');

                foreach ($enrollmentIds as $enrollmentId) {
                    $grades = $gradeRows->get($enrollmentId, collect())
                        ->map(fn (FinalGrade $grade): array => [
                            'quarter' => (string) $grade->quarter,
                            'grade' => (float) $grade->grade,
                            'is_locked' => (bool) $grade->is_locked,
                        ])
                        ->values()
                        ->all();

                    $attendance = $attendanceRows->get($enrollmentId, collect())
                        ->map(fn (Attendance $attendanceItem): array => [
                            'date' => (string) $attendanceItem->date,
                            'status' => (string) $attendanceItem->status,
                        ])
                        ->values()
                        ->all();

                    $recordsByEnrollment[(string) $enrollmentId] = [
                        'grade_summaries' => $grades,
                        'assessments' => [],
                        'attendance' => $attendance,
                    ];
                }

                $enrollmentStudentMap = Enrollment::query()
                    ->whereIn('id', $enrollmentIds)
                    ->pluck('student_id', 'id');

                $studentIds = $enrollmentStudentMap
                    ->values()
                    ->map(fn ($id): int => (int) $id)
                    ->all();

                if ($studentIds !== []) {
                    $allowedAssessmentTitles = [
                        'Historical Quiz 1',
                        'Historical Quiz 2',
                        'Historical Seatwork 1',
                        'Historical Seatwork 2',
                        'Historical Seatwork 3',
                        'Historical Assignment 1',
                        'Historical Assignment 2',
                        'Historical Assignment 3',
                        'Historical Performance Task 1',
                        'Historical Performance Task 2',
                        'Historical Quarterly Assessment',
                    ];

                    $assessmentScoreRows = StudentScore::query()
                        ->with(['gradedActivity:id,subject_assignment_id,quarter,type,title,max_score'])
                        ->whereIn('student_id', $studentIds)
                        ->whereHas('gradedActivity', function ($query) use ($selectedAssignment): void {
                            $query->where('subject_assignment_id', $selectedAssignment->id);
                        })
                        ->get(['student_id', 'graded_activity_id', 'score'])
                        ->groupBy('student_id');

                    foreach ($enrollmentIds as $enrollmentId) {
                        $studentId = (int) ($enrollmentStudentMap[$enrollmentId] ?? 0);
                        if ($studentId <= 0) {
                            continue;
                        }

                        $assessments = $assessmentScoreRows->get($studentId, collect())
                            ->map(function (StudentScore $score) use ($allowedAssessmentTitles): ?array {
                                $activity = $score->gradedActivity;
                                if (! $activity) {
                                    return null;
                                }
                                if (! in_array((string) $activity->title, $allowedAssessmentTitles, true)) {
                                    return null;
                                }

                                return [
                                    'quarter' => (string) $activity->quarter,
                                    'type' => (string) $activity->type,
                                    'title' => (string) $activity->title,
                                    'max_score' => (float) $activity->max_score,
                                    'score' => (float) $score->score,
                                ];
                            })
                            ->filter()
                            ->sortBy(function (array $row): string {
                                $quarterOrder = match ($row['quarter']) {
                                    '1' => '1',
                                    '2' => '2',
                                    '3' => '3',
                                    '4' => '4',
                                    'final' => '5',
                                    default => '9',
                                };

                                return "{$quarterOrder}-{$row['type']}-{$row['title']}";
                            })
                            ->values()
                            ->all();

                        $recordKey = (string) $enrollmentId;
                        if (isset($recordsByEnrollment[$recordKey])) {
                            $recordsByEnrollment[$recordKey]['assessments'] = $assessments;
                        }
                    }
                }
            }
        }

        return Inertia::render('teacher/historical-records/index', [
            'context' => [
                'academic_year_options' => $academicYearOptions->all(),
                'selected_academic_year_id' => $selectedAcademicYearId,
                'grade_level_options' => $gradeLevelOptions
                    ->map(fn (array $grade): array => ['id' => $grade['id'], 'name' => $grade['name']])
                    ->values()
                    ->all(),
                'selected_grade_level_id' => $selectedGradeLevelId,
                'section_options' => $sectionOptions->all(),
                'selected_section_id' => $selectedSectionId,
                'subject_options' => $subjectOptions->all(),
                'selected_subject_assignment_id' => $selectedAssignmentId,
            ],
            'students' => $students,
            'records_by_enrollment' => $recordsByEnrollment,
        ]);
    }
}
