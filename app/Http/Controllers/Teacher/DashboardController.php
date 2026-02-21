<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\SubjectAssignment;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $teacherId = (int) auth()->id();
        $today = now()->format('l');

        $todaySchedules = ClassSchedule::query()
            ->with([
                'section:id,grade_level_id,name,adviser_id',
                'section.gradeLevel:id,name',
                'subjectAssignment:id,teacher_subject_id',
                'subjectAssignment.teacherSubject:id,subject_id,teacher_id',
                'subjectAssignment.teacherSubject.subject:id,subject_name',
            ])
            ->where('day', $today)
            ->where(function ($query) use ($teacherId) {
                $query
                    ->whereHas('subjectAssignment.teacherSubject', function ($teacherQuery) use ($teacherId) {
                        $teacherQuery->where('teacher_id', $teacherId);
                    })
                    ->orWhere(function ($advisoryQuery) use ($teacherId) {
                        $advisoryQuery
                            ->whereNull('subject_assignment_id')
                            ->whereHas('section', function ($sectionQuery) use ($teacherId) {
                                $sectionQuery->where('adviser_id', $teacherId);
                            });
                    });
            })
            ->orderBy('start_time')
            ->orderBy('end_time')
            ->get()
            ->map(function (ClassSchedule $classSchedule) {
                $title = $classSchedule->subjectAssignment?->teacherSubject?->subject?->subject_name
                    ?? ($classSchedule->label ?: 'Advisory');

                $gradeLevelName = $classSchedule->section?->gradeLevel?->name;
                $sectionName = $classSchedule->section?->name;
                $sectionLabel = $sectionName;
                if ($gradeLevelName && $sectionName) {
                    $sectionLabel = "{$gradeLevelName} - {$sectionName}";
                }

                return [
                    'id' => $classSchedule->id,
                    'start' => $this->toHourMinute($classSchedule->start_time),
                    'end' => $this->toHourMinute($classSchedule->end_time),
                    'time_label' => $this->toTimeLabel($classSchedule->start_time, $classSchedule->end_time),
                    'title' => $title,
                    'section' => $sectionLabel ?: 'Unassigned',
                ];
            })
            ->values();

        $activeYear = AcademicYear::query()
            ->where('status', 'ongoing')
            ->first()
            ?? AcademicYear::query()->orderByDesc('start_date')->first();

        $pendingSubjectsCount = 0;
        $totalSubjectsCount = 0;

        if ($activeYear) {
            $currentQuarter = (string) ($activeYear->current_quarter ?: '1');

            $subjectAssignments = SubjectAssignment::query()
                ->whereHas('teacherSubject', function ($query) use ($teacherId) {
                    $query->where('teacher_id', $teacherId);
                })
                ->whereHas('section', function ($query) use ($activeYear) {
                    $query->where('academic_year_id', $activeYear->id);
                })
                ->get(['id', 'section_id']);

            $totalSubjectsCount = $subjectAssignments->count();

            if ($totalSubjectsCount > 0) {
                $enrolledCountBySection = Enrollment::query()
                    ->where('academic_year_id', $activeYear->id)
                    ->whereIn('section_id', $subjectAssignments->pluck('section_id')->unique())
                    ->where('status', 'enrolled')
                    ->selectRaw('section_id, count(*) as total')
                    ->groupBy('section_id')
                    ->pluck('total', 'section_id');

                $postedCountByAssignment = FinalGrade::query()
                    ->where('quarter', $currentQuarter)
                    ->whereIn('subject_assignment_id', $subjectAssignments->pluck('id'))
                    ->selectRaw('subject_assignment_id, count(*) as total')
                    ->groupBy('subject_assignment_id')
                    ->pluck('total', 'subject_assignment_id');

                foreach ($subjectAssignments as $subjectAssignment) {
                    $sectionCount = (int) ($enrolledCountBySection[$subjectAssignment->section_id] ?? 0);
                    $postedCount = (int) ($postedCountByAssignment[$subjectAssignment->id] ?? 0);

                    if ($sectionCount > $postedCount) {
                        $pendingSubjectsCount++;
                    }
                }
            }
        }

        return Inertia::render('teacher/dashboard', [
            'today_schedule' => $todaySchedules,
            'pending_summary' => [
                'pending_subjects_count' => $pendingSubjectsCount,
                'total_subjects_count' => $totalSubjectsCount,
                'completed_subjects_count' => max($totalSubjectsCount - $pendingSubjectsCount, 0),
            ],
        ]);
    }

    private function toHourMinute(string $timeValue): string
    {
        return substr($timeValue, 0, 5);
    }

    private function toTimeLabel(string $startTime, string $endTime): string
    {
        $start = Carbon::createFromFormat('H:i:s', $startTime)->format('h:i A');
        $end = Carbon::createFromFormat('H:i:s', $endTime)->format('h:i A');

        return "{$start} - {$end}";
    }
}
