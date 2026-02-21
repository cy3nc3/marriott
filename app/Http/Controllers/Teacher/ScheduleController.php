<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\Section;
use App\Models\SubjectAssignment;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function index(): Response
    {
        $teacherId = (int) auth()->id();

        $classSchedules = ClassSchedule::query()
            ->with([
                'section:id,grade_level_id,name',
                'section.gradeLevel:id,name',
                'subjectAssignment:id,teacher_subject_id',
                'subjectAssignment.teacherSubject:id,subject_id,teacher_id',
                'subjectAssignment.teacherSubject.subject:id,subject_name',
            ])
            ->whereHas('subjectAssignment.teacherSubject', function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->get();

        $advisorySchedules = ClassSchedule::query()
            ->with([
                'section:id,grade_level_id,name,adviser_id',
                'section.gradeLevel:id,name',
            ])
            ->whereNull('subject_assignment_id')
            ->whereHas('section', function ($query) use ($teacherId) {
                $query->where('adviser_id', $teacherId);
            })
            ->get();

        $scheduleItems = $classSchedules
            ->map(function (ClassSchedule $classSchedule) {
                return [
                    'day' => $classSchedule->day,
                    'start' => $this->toHourMinute($classSchedule->start_time),
                    'end' => $this->toHourMinute($classSchedule->end_time),
                    'title' => $classSchedule->subjectAssignment?->teacherSubject?->subject?->subject_name ?? 'Class',
                    'section' => $this->resolveSectionLabel($classSchedule),
                    'type' => 'class',
                ];
            })
            ->merge(
                $advisorySchedules->map(function (ClassSchedule $classSchedule) {
                    return [
                        'day' => $classSchedule->day,
                        'start' => $this->toHourMinute($classSchedule->start_time),
                        'end' => $this->toHourMinute($classSchedule->end_time),
                        'title' => $classSchedule->label ?: 'Advisory',
                        'section' => $this->resolveSectionLabel($classSchedule),
                        'type' => 'advisory',
                    ];
                })
            )
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

        $teacherSectionIds = SubjectAssignment::query()
            ->whereHas('teacherSubject', function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->pluck('section_id');

        $advisorySectionIds = Section::query()
            ->where('adviser_id', $teacherId)
            ->pluck('id');

        $breakItems = collect();
        $relevantSectionIds = $teacherSectionIds
            ->merge($advisorySectionIds)
            ->unique()
            ->values();

        if ($relevantSectionIds->isNotEmpty()) {
            $breakItems = ClassSchedule::query()
                ->whereIn('section_id', $relevantSectionIds)
                ->where('type', 'break')
                ->orderBy('start_time')
                ->orderBy('end_time')
                ->get()
                ->map(function (ClassSchedule $classSchedule) {
                    return [
                        'label' => $classSchedule->label ?: 'Break',
                        'start' => $this->toHourMinute($classSchedule->start_time),
                        'end' => $this->toHourMinute($classSchedule->end_time),
                    ];
                })
                ->unique(function (array $breakItem) {
                    return "{$breakItem['label']}-{$breakItem['start']}-{$breakItem['end']}";
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

        return Inertia::render('teacher/schedule/index', [
            'schedule_items' => $scheduleItems,
            'break_items' => $breakItems,
        ]);
    }

    private function toHourMinute(string $timeValue): string
    {
        return substr($timeValue, 0, 5);
    }

    private function resolveSectionLabel(ClassSchedule $classSchedule): string
    {
        $gradeLevelName = $classSchedule->section?->gradeLevel?->name;
        $sectionName = $classSchedule->section?->name;

        if ($gradeLevelName && $sectionName) {
            return "{$gradeLevelName} - {$sectionName}";
        }

        if ($sectionName) {
            return $sectionName;
        }

        return 'Unassigned';
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
