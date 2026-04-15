<?php

namespace App\Services\SchoolForms;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Setting;
use App\Models\SubjectAssignment;
use Illuminate\Support\Carbon;

class Sf2ExportBuilder
{
    /**
     * @return array{school_id: string, school_year: string, report_month: string, school_name: string, grade_level: string, section: string}
     */
    public function buildMetadata(SubjectAssignment $assignment, string $selectedMonth): array
    {
        $section = $assignment->section;

        return [
            'school_id' => (string) Setting::get('school_id', ''),
            'school_year' => (string) ($section?->academicYear?->name ?? ''),
            'report_month' => Carbon::createFromFormat('Y-m', $selectedMonth)->format('F Y'),
            'school_name' => (string) Setting::get('school_name', config('app.name', 'Marriott School')),
            'grade_level' => (string) ($section?->gradeLevel?->name ?? ''),
            'section' => (string) ($section?->name ?? ''),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildRows(SubjectAssignment $assignment, string $selectedMonth): array
    {
        $section = $assignment->section;

        if (! $section) {
            return [];
        }

        $days = $this->attendanceDaysForMonth($selectedMonth, $section->academicYear);
        $enrollments = Enrollment::query()
            ->with('student:id,first_name,middle_name,last_name,gender')
            ->where('section_id', $section->id)
            ->where('academic_year_id', $section->academic_year_id)
            ->where('status', 'enrolled')
            ->get(['id', 'student_id'])
            ->sortBy(function (Enrollment $enrollment): string {
                return strtolower(trim("{$enrollment->student?->last_name} {$enrollment->student?->first_name}"));
            })
            ->values();

        $attendanceMap = Attendance::query()
            ->where('subject_assignment_id', $assignment->id)
            ->whereIn('enrollment_id', $enrollments->pluck('id')->all())
            ->whereBetween('date', [reset($days) ?: now()->toDateString(), end($days) ?: now()->toDateString()])
            ->get(['enrollment_id', 'date', 'status'])
            ->mapWithKeys(function (Attendance $attendance): array {
                return ["{$attendance->enrollment_id}|{$attendance->date}" => $attendance->status];
            });

        return $enrollments
            ->map(function (Enrollment $enrollment) use ($days, $attendanceMap): array {
                $attendanceStatuses = collect($days)
                    ->map(function (string $date) use ($enrollment, $attendanceMap): string {
                        return (string) $attendanceMap->get("{$enrollment->id}|{$date}", Attendance::STATUS_PRESENT);
                    })
                    ->values()
                    ->all();

                $absentCount = collect($attendanceStatuses)
                    ->filter(fn (string $status): bool => $status === Attendance::STATUS_ABSENT)
                    ->count();

                return [
                    'gender' => (string) ($enrollment->student?->gender ?? ''),
                    'name' => $this->formatSf2Name(
                        (string) ($enrollment->student?->last_name ?? ''),
                        (string) ($enrollment->student?->first_name ?? ''),
                        (string) ($enrollment->student?->middle_name ?? '')
                    ),
                    'attendance' => $attendanceStatuses,
                    'total_absent' => $absentCount,
                    'total_present' => count($attendanceStatuses) - $absentCount,
                    'remarks' => '',
                ];
            })
            ->all();
    }

    /**
     * @return list<string>
     */
    private function attendanceDaysForMonth(string $selectedMonth, ?AcademicYear $academicYear): array
    {
        $monthStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();

        return collect(range(1, $monthStart->daysInMonth))
            ->map(function (int $day) use ($monthStart): Carbon {
                return $monthStart->copy()->day($day);
            })
            ->reject(fn (Carbon $date): bool => in_array($date->format('D'), ['Sat', 'Sun'], true))
            ->filter(function (Carbon $date) use ($academicYear): bool {
                if (! $academicYear?->start_date || ! $academicYear?->end_date) {
                    return true;
                }

                return $date->toDateString() >= (string) $academicYear->start_date
                    && $date->toDateString() <= (string) $academicYear->end_date;
            })
            ->map(fn (Carbon $date): string => $date->toDateString())
            ->values()
            ->all();
    }

    private function formatSf2Name(string $lastName, string $firstName, string $middleName): string
    {
        return collect([$lastName, $firstName, $middleName])
            ->map(fn (string $value): string => trim($value))
            ->filter()
            ->implode(', ');
    }
}
