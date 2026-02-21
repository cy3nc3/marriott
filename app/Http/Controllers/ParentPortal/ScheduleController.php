<?php

namespace App\Http\Controllers\ParentPortal;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function index(): Response
    {
        $student = $this->resolveStudent(auth()->user());
        $enrollment = $student ? $this->resolveCurrentEnrollment($student) : null;

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
                ->values();

            $breakItems = $scheduleRows
                ->where('type', 'break')
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

        return Inertia::render('parent/schedule/index', [
            'student_name' => $student ? trim("{$student->first_name} {$student->last_name}") : null,
            'schedule_items' => $scheduleItems,
            'break_items' => $breakItems,
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

    private function resolveCurrentEnrollment(Student $student): ?Enrollment
    {
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
            ->where('status', 'enrolled')
            ->latest('id')
            ->first();
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
}
