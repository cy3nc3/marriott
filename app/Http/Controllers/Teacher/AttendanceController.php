<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\IndexAttendanceRequest;
use App\Http\Requests\Teacher\StoreAttendanceRequest;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\SubjectAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(IndexAttendanceRequest $request): Response
    {
        $validated = $request->validated();
        $teacherId = (int) auth()->id();

        $activeAcademicYear = AcademicYear::query()
            ->where('status', 'ongoing')
            ->first()
            ?? AcademicYear::query()->orderByDesc('start_date')->first();
        $selectedMonth = (string) ($validated['month'] ?? now()->format('Y-m'));
        $monthStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $featureLocked = ! $this->isTeacherAcademicFeatureAvailable($activeAcademicYear);
        $monthOutOfScope = ! $this->doesMonthOverlapAcademicYear(
            $monthStart->toDateString(),
            $monthEnd->toDateString(),
            $activeAcademicYear
        );
        $featureLockMessage = $featureLocked
            ? $this->resolveFeatureLockMessage($activeAcademicYear, 'Attendance')
            : null;

        $days = collect(range(1, $monthStart->daysInMonth))
            ->map(function (int $day) use ($monthStart) {
                $date = $monthStart->copy()->day($day);

                return [
                    'date' => $date->toDateString(),
                    'day' => $date->format('j'),
                    'weekday' => strtoupper($date->format('D')),
                ];
            })
            ->filter(function (array $dayItem) {
                return ! in_array((string) $dayItem['weekday'], ['SAT', 'SUN'], true);
            })
            ->filter(function (array $dayItem) use ($activeAcademicYear) {
                return $this->isDateWithinAcademicYear($dayItem['date'], $activeAcademicYear);
            })
            ->values();

        $classAssignments = SubjectAssignment::query()
            ->with([
                'section:id,academic_year_id,grade_level_id,name',
                'section.gradeLevel:id,name',
                'teacherSubject:id,teacher_id,subject_id',
                'teacherSubject.subject:id,subject_name',
            ])
            ->whereHas('teacherSubject', function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->when($activeAcademicYear, function ($query) use ($activeAcademicYear) {
                $query->whereHas('section', function ($sectionQuery) use ($activeAcademicYear) {
                    $sectionQuery->where('academic_year_id', $activeAcademicYear->id);
                });
            })
            ->get(['id', 'section_id', 'teacher_subject_id'])
            ->sortBy(function (SubjectAssignment $assignment) {
                $gradeLevelOrder = (int) ($assignment->section?->gradeLevel?->level_order ?? 0);
                $sectionName = strtolower((string) ($assignment->section?->name ?? ''));
                $subjectName = strtolower((string) ($assignment->teacherSubject?->subject?->subject_name ?? ''));

                return sprintf('%03d-%s-%s', $gradeLevelOrder, $sectionName, $subjectName);
            })
            ->values();

        $classOptions = $classAssignments
            ->map(function (SubjectAssignment $assignment) {
                $gradeLevelName = $assignment->section?->gradeLevel?->name;
                $sectionName = $assignment->section?->name;
                $subjectName = $assignment->teacherSubject?->subject?->subject_name;

                $sectionLabel = $gradeLevelName && $sectionName
                    ? "{$gradeLevelName} - {$sectionName}"
                    : ($sectionName ?: 'Unassigned Section');

                return [
                    'id' => (int) $assignment->id,
                    'label' => $subjectName
                        ? "{$sectionLabel} - {$subjectName}"
                        : $sectionLabel,
                ];
            })
            ->values();

        $allowedAssignmentIds = $classOptions
            ->pluck('id')
            ->all();
        $selectedAssignmentId = (int) ($validated['subject_assignment_id'] ?? ($classOptions->first()['id'] ?? 0));

        if (! in_array($selectedAssignmentId, $allowedAssignmentIds, true)) {
            $selectedAssignmentId = (int) ($classOptions->first()['id'] ?? 0);
        }

        $selectedAssignment = $classAssignments->firstWhere('id', $selectedAssignmentId);

        $enrollments = collect();
        $attendanceMap = collect();

        if ($selectedAssignment?->section) {
            $section = $selectedAssignment->section;

            $enrollments = Enrollment::query()
                ->with('student:id,first_name,last_name')
                ->where('section_id', $section->id)
                ->where('academic_year_id', $section->academic_year_id)
                ->where('status', 'enrolled')
                ->get(['id', 'student_id'])
                ->sortBy(function (Enrollment $enrollment) {
                    return strtolower(trim("{$enrollment->student?->last_name} {$enrollment->student?->first_name}"));
                })
                ->values();

            $enrollmentIds = $enrollments
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (count($enrollmentIds) > 0) {
                $attendanceMap = Attendance::query()
                    ->where('subject_assignment_id', $selectedAssignment->id)
                    ->whereIn('enrollment_id', $enrollmentIds)
                    ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->get(['enrollment_id', 'date', 'status'])
                    ->mapWithKeys(function (Attendance $attendance) {
                        return ["{$attendance->enrollment_id}|{$attendance->date}" => $attendance->status];
                    });
            }
        }

        $rows = $enrollments
            ->map(function (Enrollment $enrollment) use ($days, $attendanceMap) {
                $statuses = $days
                    ->mapWithKeys(function (array $dayItem) use ($enrollment, $attendanceMap) {
                        $date = (string) $dayItem['date'];
                        $key = "{$enrollment->id}|{$date}";

                        return [$date => $attendanceMap->get($key, Attendance::STATUS_PRESENT)];
                    })
                    ->all();

                return [
                    'enrollment_id' => (int) $enrollment->id,
                    'student_name' => trim("{$enrollment->student?->last_name}, {$enrollment->student?->first_name}"),
                    'statuses' => $statuses,
                ];
            })
            ->values();

        return Inertia::render('teacher/attendance/index', [
            'context' => [
                'class_options' => $classOptions,
                'selected_subject_assignment_id' => $selectedAssignmentId > 0 ? $selectedAssignmentId : null,
                'selected_month' => $selectedMonth,
                'active_school_year' => $activeAcademicYear?->name,
            ],
            'feature_lock' => [
                'is_locked' => $featureLocked,
                'message' => $featureLockMessage,
            ],
            'month_scope' => [
                'is_out_of_scope' => $monthOutOfScope,
                'message' => $monthOutOfScope
                    ? $this->resolveMonthScopeLockMessage($activeAcademicYear)
                    : null,
            ],
            'days' => $days,
            'rows' => $rows,
            'status_options' => Attendance::STATUSES,
        ]);
    }

    public function store(StoreAttendanceRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $teacherId = (int) auth()->id();

        $assignment = SubjectAssignment::query()
            ->with('section:id,academic_year_id')
            ->whereKey((int) $validated['subject_assignment_id'])
            ->whereHas('teacherSubject', function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->first();

        if (! $assignment?->section) {
            return back()->with('error', 'You can only update attendance for your assigned classes.');
        }
        $assignmentAcademicYear = AcademicYear::query()->find($assignment->section->academic_year_id);
        if (! $this->isTeacherAcademicFeatureAvailable($assignmentAcademicYear)) {
            return back()->with('error', $this->resolveFeatureLockMessage($assignmentAcademicYear, 'Attendance'));
        }

        $monthStart = Carbon::createFromFormat('Y-m', (string) $validated['month'])->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $allowedEnrollmentIds = Enrollment::query()
            ->where('section_id', $assignment->section->id)
            ->where('academic_year_id', $assignment->section->academic_year_id)
            ->where('status', 'enrolled')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $allowedEnrollmentLookup = collect($allowedEnrollmentIds)->flip();

        foreach ($validated['entries'] as $entry) {
            $enrollmentId = (int) $entry['enrollment_id'];
            $dateValue = Carbon::parse((string) $entry['date'])->toDateString();
            $status = (string) $entry['status'];

            if (! $allowedEnrollmentLookup->has($enrollmentId)) {
                return back()->with('error', 'Attendance entry includes an invalid learner for this class.');
            }

            if ($dateValue < $monthStart->toDateString() || $dateValue > $monthEnd->toDateString()) {
                return back()->with('error', 'Attendance entry date is outside the selected month.');
            }

            if (! $this->isDateWithinAcademicYear($dateValue, $assignmentAcademicYear)) {
                return back()->with('error', 'Attendance entry date is outside the configured school year range.');
            }

            if ($status === Attendance::STATUS_PRESENT) {
                Attendance::query()
                    ->where('subject_assignment_id', $assignment->id)
                    ->where('enrollment_id', $enrollmentId)
                    ->whereDate('date', $dateValue)
                    ->delete();

                continue;
            }

            Attendance::query()->updateOrCreate(
                [
                    'subject_assignment_id' => $assignment->id,
                    'enrollment_id' => $enrollmentId,
                    'date' => $dateValue,
                ],
                [
                    'status' => $status,
                    'remarks' => null,
                ]
            );
        }

        return back()->with('success', 'Attendance saved.');
    }

    private function isTeacherAcademicFeatureAvailable(?AcademicYear $academicYear): bool
    {
        if (! $academicYear) {
            return false;
        }

        if ((string) $academicYear->status !== 'ongoing') {
            return false;
        }

        if (! in_array((string) $academicYear->current_quarter, ['1', '2', '3', '4'], true)) {
            return false;
        }

        return true;
    }

    private function resolveFeatureLockMessage(?AcademicYear $academicYear, string $featureLabel): string
    {
        if (! $academicYear) {
            return "{$featureLabel} is unavailable because no active school year is configured.";
        }

        if ((string) $academicYear->status !== 'ongoing') {
            return "{$featureLabel} is unavailable during pre-opening. It will be available once the school year is marked as ongoing.";
        }

        return "{$featureLabel} is unavailable because the current quarter is outside 1st to 4th quarter.";
    }

    private function doesMonthOverlapAcademicYear(
        string $monthStartDate,
        string $monthEndDate,
        ?AcademicYear $academicYear
    ): bool {
        if (! $academicYear?->start_date || ! $academicYear?->end_date) {
            return true;
        }

        return $monthEndDate >= (string) $academicYear->start_date
            && $monthStartDate <= (string) $academicYear->end_date;
    }

    private function isDateWithinAcademicYear(string $dateValue, ?AcademicYear $academicYear): bool
    {
        if (! $academicYear?->start_date || ! $academicYear?->end_date) {
            return true;
        }

        return $dateValue >= (string) $academicYear->start_date
            && $dateValue <= (string) $academicYear->end_date;
    }

    private function resolveMonthScopeLockMessage(?AcademicYear $academicYear): string
    {
        if (! $academicYear?->start_date || ! $academicYear?->end_date) {
            return 'The selected month is outside the editable attendance range.';
        }

        $startDateLabel = Carbon::parse((string) $academicYear->start_date)->format('F j, Y');
        $endDateLabel = Carbon::parse((string) $academicYear->end_date)->format('F j, Y');

        return "The selected month is outside the school year range ({$startDateLabel} to {$endDateLabel}).";
    }
}
