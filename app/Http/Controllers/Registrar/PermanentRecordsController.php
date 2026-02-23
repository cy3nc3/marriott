<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\PermanentRecord;
use App\Models\Student;
use Inertia\Inertia;
use Inertia\Response;

class PermanentRecordsController extends Controller
{
    public function index(): Response
    {
        $students = Student::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'lrn', 'first_name', 'last_name'])
            ->map(function (Student $student): array {
                return [
                    'id' => (int) $student->id,
                    'name' => trim("{$student->first_name} {$student->last_name}"),
                    'lrn' => $student->lrn,
                ];
            })
            ->values();

        $selectedStudentId = (int) (request()->integer('student_id')
            ?: ($students->first()['id'] ?? 0));

        $selectedStudent = $selectedStudentId > 0
            ? Student::query()
                ->with([
                    'enrollments' => function ($query) {
                        $query
                            ->with(['academicYear:id,name,status', 'gradeLevel:id,name', 'section:id,name'])
                            ->latest('id');
                    },
                ])
                ->find($selectedStudentId)
            : null;

        $selectedEnrollment = $selectedStudent?->enrollments
            ->first(function (Enrollment $enrollment): bool {
                return $enrollment->academicYear?->status === 'ongoing';
            })
            ?? $selectedStudent?->enrollments?->first();

        $selectedStudentPayload = null;
        if ($selectedStudent) {
            $assignment = $selectedEnrollment?->gradeLevel?->name;
            if ($selectedEnrollment?->gradeLevel?->name && $selectedEnrollment?->section?->name) {
                $assignment = "{$selectedEnrollment->gradeLevel->name} - {$selectedEnrollment->section->name}";
            }

            $selectedStudentPayload = [
                'id' => (int) $selectedStudent->id,
                'name' => trim("{$selectedStudent->first_name} {$selectedStudent->last_name}"),
                'lrn' => $selectedStudent->lrn,
                'current_assignment' => $assignment ?: 'Unassigned',
            ];
        }

        $records = collect();
        if ($selectedStudent) {
            $records = PermanentRecord::query()
                ->with(['academicYear:id,name', 'gradeLevel:id,name'])
                ->where('student_id', $selectedStudent->id)
                ->orderByDesc('academic_year_id')
                ->orderByDesc('id')
                ->get()
                ->map(function (PermanentRecord $record) use ($selectedStudent): array {
                    return [
                        'id' => (int) $record->id,
                        'school_year' => $record->academicYear?->name ?? 'N/A',
                        'grade_level' => $record->gradeLevel?->name ?? 'N/A',
                        'school_name' => $record->school_name ?: 'Marriott School',
                        'status' => $record->status,
                        'failed_subject_count' => (int) $record->failed_subject_count,
                        'subjects' => $this->resolveRecordSubjects(
                            (int) $selectedStudent->id,
                            (int) $record->academic_year_id
                        ),
                    ];
                })
                ->values();
        }

        return Inertia::render('registrar/permanent-records/index', [
            'students' => $students,
            'selected_student' => $selectedStudentPayload,
            'records' => $records,
            'filters' => [
                'student_id' => $selectedStudentId > 0 ? $selectedStudentId : null,
            ],
        ]);
    }

    /**
     * @return array<int, array{subject: string, q1: string, q2: string, q3: string, q4: string, final: string}>
     */
    private function resolveRecordSubjects(int $studentId, int $academicYearId): array
    {
        $enrollment = Enrollment::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->latest('id')
            ->first(['id']);

        if (! $enrollment) {
            return [];
        }

        $quarterGrades = FinalGrade::query()
            ->with('subjectAssignment.teacherSubject.subject:id,subject_name')
            ->where('enrollment_id', $enrollment->id)
            ->whereIn('quarter', ['1', '2', '3', '4'])
            ->get();

        return $quarterGrades
            ->groupBy(function (FinalGrade $grade) {
                return $grade->subjectAssignment?->teacherSubject?->subject?->subject_name ?? 'Subject';
            })
            ->map(function ($grades, string $subjectName): array {
                $gradeByQuarter = collect($grades)
                    ->keyBy(fn (FinalGrade $grade): string => (string) $grade->quarter);

                $quarterValues = collect(['1', '2', '3', '4'])
                    ->map(function (string $quarter) use ($gradeByQuarter): ?float {
                        $value = $gradeByQuarter->get($quarter)?->grade;
                        if ($value === null) {
                            return null;
                        }

                        return (float) $value;
                    })
                    ->filter(fn (?float $value) => $value !== null)
                    ->values();

                $finalAverage = $quarterValues->isNotEmpty()
                    ? round((float) $quarterValues->avg(), 2)
                    : null;

                return [
                    'subject' => $subjectName,
                    'q1' => $this->formatGradeValue($gradeByQuarter->get('1')?->grade),
                    'q2' => $this->formatGradeValue($gradeByQuarter->get('2')?->grade),
                    'q3' => $this->formatGradeValue($gradeByQuarter->get('3')?->grade),
                    'q4' => $this->formatGradeValue($gradeByQuarter->get('4')?->grade),
                    'final' => $this->formatGradeValue($finalAverage),
                ];
            })
            ->sortBy('subject')
            ->values()
            ->all();
    }

    private function formatGradeValue(null|float|string $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $numeric = (float) $value;

        if (fmod($numeric, 1.0) === 0.0) {
            return number_format($numeric, 0, '.', '');
        }

        return number_format($numeric, 2, '.', '');
    }
}
