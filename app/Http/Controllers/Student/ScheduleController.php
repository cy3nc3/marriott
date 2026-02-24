<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function index(Request $request): Response
    {
        $student = $this->resolveStudent(auth()->user());
        $schoolYearOptions = $student
            ? $this->resolveSchoolYearOptions($student)
            : collect();
        $selectedSchoolYearId = $this->resolveSelectedSchoolYearId(
            $schoolYearOptions,
            $request->integer('academic_year_id')
        );
        $enrollment = $student
            ? $this->resolveCurrentEnrollment($student, $selectedSchoolYearId)
            : null;
        $isDepartedReadOnly = $enrollment
            ? in_array($enrollment->status, ['transferred_out', 'dropped_out', 'dropped'], true)
            : false;

        $scheduleItems = collect();
        $breakItems = collect();

        if ($enrollment?->section_id) {
            $scheduleRows = ClassSchedule::query()
                ->with([
                    'section:id,adviser_id',
                    'section.adviser:id,first_name,last_name',
                    'subjectAssignment:id,teacher_subject_id',
                    'subjectAssignment.teacherSubject:id,teacher_id,subject_id',
                    'subjectAssignment.teacherSubject.subject:id,subject_name',
                    'subjectAssignment.teacherSubject.teacher:id,first_name,last_name',
                ])
                ->where('section_id', $enrollment->section_id)
                ->orderBy('day')
                ->orderBy('start_time')
                ->orderBy('end_time')
                ->get();

            $scheduleItems = $scheduleRows
                ->whereIn('type', ['academic', 'advisory'])
                ->map(function (ClassSchedule $classSchedule) {
                    return [
                        'day' => $classSchedule->day,
                        'start' => $this->toHourMinute($classSchedule->start_time),
                        'end' => $this->toHourMinute($classSchedule->end_time),
                        'title' => $this->resolveScheduleTitle($classSchedule),
                        'teacher' => $this->resolveTeacherName($classSchedule),
                        'type' => $classSchedule->type === 'advisory' ? 'advisory' : 'class',
                    ];
                })
                ->sort(function (array $left, array $right) {
                    $leftSortValue = sprintf(
                        '%02d-%s',
                        $this->dayOrder($left['day']),
                        $left['start']
                    );
                    $rightSortValue = sprintf(
                        '%02d-%s',
                        $this->dayOrder($right['day']),
                        $right['start']
                    );

                    return $leftSortValue <=> $rightSortValue;
                })
                ->values();

            $breakItems = $scheduleRows
                ->where('type', 'break')
                ->sort(function (ClassSchedule $left, ClassSchedule $right) {
                    $leftSortValue = sprintf(
                        '%02d-%s',
                        $this->dayOrder($left->day),
                        $this->toHourMinute($left->start_time)
                    );
                    $rightSortValue = sprintf(
                        '%02d-%s',
                        $this->dayOrder($right->day),
                        $this->toHourMinute($right->start_time)
                    );

                    return $leftSortValue <=> $rightSortValue;
                })
                ->map(function (ClassSchedule $classSchedule) {
                    return [
                        'label' => $classSchedule->label ?: 'Break',
                        'start' => $this->toHourMinute($classSchedule->start_time),
                        'end' => $this->toHourMinute($classSchedule->end_time),
                    ];
                })
                ->values();
        }

        if ($breakItems->isEmpty()) {
            $breakItems = collect([
                [
                    'label' => 'Recess Break',
                    'start' => '10:00',
                    'end' => '10:30',
                ],
                [
                    'label' => 'Lunch Break',
                    'start' => '12:00',
                    'end' => '13:00',
                ],
            ]);
        }

        return Inertia::render('student/schedule/index', [
            'schedule_items' => $scheduleItems,
            'break_items' => $breakItems,
            'school_year_options' => $schoolYearOptions->all(),
            'selected_school_year_id' => $selectedSchoolYearId,
            'is_departed_read_only' => $isDepartedReadOnly,
        ]);
    }

    private function resolveStudent(?User $user): ?Student
    {
        if (! $user) {
            return null;
        }

        return Student::query()
            ->where('user_id', $user->id)
            ->first();
    }

    private function resolveCurrentEnrollment(Student $student, ?int $academicYearId = null): ?Enrollment
    {
        if ($academicYearId) {
            $selectedEnrollment = Enrollment::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', $academicYearId)
                ->whereIn('status', ['enrolled', 'transferred_out', 'dropped_out', 'dropped'])
                ->latest('id')
                ->first();

            if ($selectedEnrollment) {
                return $selectedEnrollment;
            }
        }

        $activeYearId = AcademicYear::query()
            ->where('status', 'ongoing')
            ->value('id');

        if ($activeYearId) {
            $activeEnrollment = Enrollment::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', $activeYearId)
                ->where('status', 'enrolled')
                ->first();

            if ($activeEnrollment) {
                return $activeEnrollment;
            }
        }

        return Enrollment::query()
            ->where('student_id', $student->id)
            ->whereIn('status', ['enrolled', 'transferred_out', 'dropped_out', 'dropped'])
            ->latest('id')
            ->first();
    }

    private function resolveSchoolYearOptions(Student $student): Collection
    {
        return AcademicYear::query()
            ->select(['academic_years.id', 'academic_years.name', 'academic_years.status', 'academic_years.start_date'])
            ->join('enrollments', 'enrollments.academic_year_id', '=', 'academic_years.id')
            ->where('enrollments.student_id', $student->id)
            ->whereIn('enrollments.status', ['enrolled', 'transferred_out', 'dropped_out', 'dropped'])
            ->distinct()
            ->orderByDesc('academic_years.start_date')
            ->get()
            ->map(function (AcademicYear $academicYear): array {
                return [
                    'id' => (int) $academicYear->id,
                    'name' => $academicYear->name,
                    'status' => $academicYear->status,
                ];
            })
            ->values();
    }

    private function resolveSelectedSchoolYearId(Collection $schoolYearOptions, ?int $requestedSchoolYearId): ?int
    {
        if ($requestedSchoolYearId && $schoolYearOptions->pluck('id')->contains($requestedSchoolYearId)) {
            return $requestedSchoolYearId;
        }

        $ongoingOption = $schoolYearOptions->firstWhere('status', 'ongoing');
        if ($ongoingOption) {
            return (int) $ongoingOption['id'];
        }

        return $schoolYearOptions->first()['id'] ?? null;
    }

    private function resolveScheduleTitle(ClassSchedule $classSchedule): string
    {
        if ($classSchedule->type === 'advisory') {
            return $classSchedule->label ?: 'Advisory';
        }

        return $classSchedule->subjectAssignment?->teacherSubject?->subject?->subject_name ?: 'Class';
    }

    private function resolveTeacherName(ClassSchedule $classSchedule): string
    {
        $teacher = $classSchedule->subjectAssignment?->teacherSubject?->teacher;
        if ($teacher) {
            return trim("{$teacher->first_name} {$teacher->last_name}");
        }

        $adviser = $classSchedule->section?->adviser;
        if ($adviser) {
            return trim("{$adviser->first_name} {$adviser->last_name}");
        }

        return 'TBA';
    }

    private function toHourMinute(string $timeValue): string
    {
        return substr($timeValue, 0, 5);
    }

    private function dayOrder(string $day): int
    {
        return match ($day) {
            'Monday' => 1,
            'Tuesday' => 2,
            'Wednesday' => 3,
            'Thursday' => 4,
            'Friday' => 5,
            'Saturday' => 6,
            'Sunday' => 7,
            default => 99,
        };
    }
}
