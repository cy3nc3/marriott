<?php

namespace App\Http\Controllers\ParentPortal;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
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

        $attendanceRows = collect();

        if ($enrollment) {
            $attendanceRows = Attendance::query()
                ->with('subjectAssignment.teacherSubject.subject:id,subject_name')
                ->where('enrollment_id', $enrollment->id)
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->get()
                ->map(function (Attendance $attendance): array {
                    return [
                        'date' => (string) $attendance->date,
                        'subject' => (string) ($attendance->subjectAssignment?->teacherSubject?->subject?->subject_name ?? 'Unassigned Subject'),
                        'status' => (string) $attendance->status,
                        'remarks' => $attendance->remarks,
                    ];
                })
                ->values();
        }

        return Inertia::render('parent/attendance/index', [
            'context' => [
                'student_name' => $student ? trim("{$student->first_name} {$student->last_name}") : null,
                'school_year' => $enrollment?->academicYear?->name,
            ],
            'attendance_rows' => $attendanceRows->all(),
            'summary' => [
                'present' => (int) $attendanceRows->where('status', Attendance::STATUS_PRESENT)->count(),
                'absent' => (int) $attendanceRows->where('status', Attendance::STATUS_ABSENT)->count(),
                'tardy_late_comer' => (int) $attendanceRows->where('status', Attendance::STATUS_TARDY_LATE_COMER)->count(),
                'tardy_cutting_classes' => (int) $attendanceRows->where('status', Attendance::STATUS_TARDY_CUTTING_CLASSES)->count(),
            ],
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

        return $user->students()
            ->orderBy('students.last_name')
            ->orderBy('students.first_name')
            ->first();
    }

    private function resolveCurrentEnrollment(Student $student, ?int $academicYearId = null): ?Enrollment
    {
        if ($academicYearId) {
            $selectedEnrollment = Enrollment::query()
                ->with('academicYear:id,name')
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
                ->with('academicYear:id,name')
                ->where('student_id', $student->id)
                ->where('academic_year_id', $activeYearId)
                ->where('status', 'enrolled')
                ->first();

            if ($activeEnrollment) {
                return $activeEnrollment;
            }
        }

        return Enrollment::query()
            ->with('academicYear:id,name')
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
}
