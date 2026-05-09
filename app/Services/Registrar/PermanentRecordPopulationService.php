<?php

namespace App\Services\Registrar;

use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\GradeLevel;
use App\Models\GradeRelease;
use App\Models\PermanentRecord;
use App\Models\Section;
use App\Models\Student;
use App\Models\SubjectAssignment;

class PermanentRecordPopulationService
{
    /**
     * @return array{processed: int, released_quarters: list<string>}
     */
    public function populateForReleasedQuarter(Section $section, string $quarter): array
    {
        $releasedQuarters = GradeRelease::query()
            ->where('academic_year_id', $section->academic_year_id)
            ->where('section_id', $section->id)
            ->pluck('quarter')
            ->map(fn ($releasedQuarter): string => (string) $releasedQuarter)
            ->unique()
            ->sort()
            ->values();

        if (! $releasedQuarters->contains($quarter)) {
            return [
                'processed' => 0,
                'released_quarters' => $releasedQuarters->all(),
            ];
        }

        $subjectAssignmentIds = SubjectAssignment::query()
            ->where('section_id', $section->id)
            ->pluck('id');

        if ($subjectAssignmentIds->isEmpty()) {
            return [
                'processed' => 0,
                'released_quarters' => $releasedQuarters->all(),
            ];
        }

        $enrollments = Enrollment::query()
            ->with('gradeLevel:id,name,level_order')
            ->where('academic_year_id', $section->academic_year_id)
            ->where('section_id', $section->id)
            ->where('status', 'enrolled')
            ->orderBy('id')
            ->get();

        $processed = 0;
        $releasedQuarterValues = $releasedQuarters->all();
        $allQuartersReleased = $releasedQuarters->sort()->values()->all() === ['1', '2', '3', '4'];

        foreach ($enrollments as $enrollment) {
            $annualSubjectGrades = $this->computeReleasedSubjectAverages(
                $enrollment,
                $subjectAssignmentIds->all(),
                $releasedQuarterValues
            );

            if ($annualSubjectGrades === []) {
                continue;
            }

            $failedSubjectCount = $allQuartersReleased
                ? collect($annualSubjectGrades)
                    ->filter(fn (float $grade): bool => $grade < 75)
                    ->count()
                : 0;

            $status = $allQuartersReleased
                ? $this->resolveFinalStatus($enrollment, (int) $failedSubjectCount)
                : 'in_progress';

            PermanentRecord::query()->updateOrCreate(
                [
                    'student_id' => $enrollment->student_id,
                    'academic_year_id' => $section->academic_year_id,
                ],
                [
                    'school_name' => 'Marriott School',
                    'grade_level_id' => $enrollment->grade_level_id,
                    'general_average' => round((float) collect($annualSubjectGrades)->avg(), 2),
                    'status' => $status,
                    'failed_subject_count' => $failedSubjectCount,
                    'conditional_resolved_at' => null,
                    'conditional_resolution_notes' => null,
                    'remarks' => $this->buildRemarks($status, (int) $failedSubjectCount, $releasedQuarterValues),
                ]
            );

            $this->refreshStudentRemedialFlag((int) $enrollment->student_id);
            $processed++;
        }

        return [
            'processed' => $processed,
            'released_quarters' => $releasedQuarterValues,
        ];
    }

    /**
     * @param  list<int>  $subjectAssignmentIds
     * @param  list<string>  $releasedQuarters
     * @return array<int, float>
     */
    private function computeReleasedSubjectAverages(
        Enrollment $enrollment,
        array $subjectAssignmentIds,
        array $releasedQuarters,
    ): array {
        $grades = FinalGrade::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereIn('subject_assignment_id', $subjectAssignmentIds)
            ->whereIn('quarter', $releasedQuarters)
            ->where('is_locked', true)
            ->get(['subject_assignment_id', 'quarter', 'grade']);

        $subjectAverages = [];

        foreach ($subjectAssignmentIds as $subjectAssignmentId) {
            $quarterGrades = $grades
                ->where('subject_assignment_id', $subjectAssignmentId)
                ->pluck('grade')
                ->map(fn ($grade): float => (float) $grade)
                ->values();

            if ($quarterGrades->isEmpty()) {
                continue;
            }

            $subjectAverages[(int) $subjectAssignmentId] = round((float) $quarterGrades->avg(), 2);
        }

        return $subjectAverages;
    }

    private function resolveFinalStatus(Enrollment $enrollment, int $failedSubjectCount): string
    {
        if ($this->isTerminalGradeLevel($enrollment)) {
            return 'completed';
        }

        if ($failedSubjectCount <= 0) {
            return 'promoted';
        }

        if ($failedSubjectCount <= 2) {
            return 'conditional';
        }

        return 'retained';
    }

    private function isTerminalGradeLevel(Enrollment $enrollment): bool
    {
        $gradeLevel = $enrollment->gradeLevel
            ?? GradeLevel::query()->find($enrollment->grade_level_id);

        if (! $gradeLevel) {
            return true;
        }

        return ! GradeLevel::query()
            ->where('level_order', '>', $gradeLevel->level_order)
            ->exists();
    }

    /**
     * @param  list<string>  $releasedQuarters
     */
    private function buildRemarks(string $status, int $failedSubjectCount, array $releasedQuarters): string
    {
        if ($status === 'in_progress') {
            return 'Auto-populated from released quarter grades: Q'.implode(', Q', $releasedQuarters).'.';
        }

        if ($status === 'promoted') {
            return 'Passed annual requirements after final quarter release.';
        }

        if ($status === 'conditional') {
            return "Conditional promotion with {$failedSubjectCount} failed subject(s).";
        }

        if ($status === 'retained') {
            return "Retained due to {$failedSubjectCount} failed subjects.";
        }

        return 'Completed terminal grade level.';
    }

    private function refreshStudentRemedialFlag(int $studentId): void
    {
        $hasUnresolvedConditionals = PermanentRecord::query()
            ->where('student_id', $studentId)
            ->where('status', 'conditional')
            ->whereNull('conditional_resolved_at')
            ->exists();

        Student::query()
            ->whereKey($studentId)
            ->update([
                'is_for_remedial' => $hasUnresolvedConditionals,
            ]);
    }
}
