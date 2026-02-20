<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\GradeLevel;
use App\Models\RemedialRecord;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RemedialEntryController extends Controller
{
    public function index(Request $request): Response
    {
        $academicYears = AcademicYear::query()
            ->orderByDesc('start_date')
            ->get(['id', 'name']);

        $gradeLevels = GradeLevel::query()
            ->orderBy('level_order')
            ->get(['id', 'name']);

        $selectedAcademicYearId = (int) ($request->input('academic_year_id')
            ?: AcademicYear::query()->where('status', 'ongoing')->value('id')
            ?: $academicYears->first()?->id);

        $selectedGradeLevelId = $request->input('grade_level_id')
            ? (int) $request->input('grade_level_id')
            : null;

        $search = trim((string) $request->input('search', ''));

        $students = Student::query()
            ->with([
                'enrollments' => function ($query) use ($selectedAcademicYearId) {
                    $query
                        ->where('academic_year_id', $selectedAcademicYearId)
                        ->with(['gradeLevel:id,name', 'section:id,name']);
                },
            ])
            ->where(function ($query) use ($selectedAcademicYearId) {
                $query
                    ->where('is_for_remedial', true)
                    ->orWhereHas('remedialRecords', function ($recordQuery) use ($selectedAcademicYearId) {
                        $recordQuery->where('academic_year_id', $selectedAcademicYearId);
                    });
            })
            ->when($selectedGradeLevelId, function ($query) use ($selectedGradeLevelId, $selectedAcademicYearId) {
                $query->whereHas('enrollments', function ($enrollmentQuery) use ($selectedGradeLevelId, $selectedAcademicYearId) {
                    $enrollmentQuery
                        ->where('academic_year_id', $selectedAcademicYearId)
                        ->where('grade_level_id', $selectedGradeLevelId);
                });
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('lrn', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $studentList = $students->map(function (Student $student) {
            $enrollment = $student->enrollments->first();

            return [
                'id' => $student->id,
                'lrn' => $student->lrn,
                'name' => trim("{$student->first_name} {$student->last_name}"),
                'grade_level_id' => $enrollment?->grade_level_id,
                'grade_and_section' => $enrollment?->gradeLevel?->name && $enrollment?->section?->name
                    ? "{$enrollment->gradeLevel->name} - {$enrollment->section->name}"
                    : 'Unassigned',
            ];
        })->values();

        $selectedStudentId = (int) ($request->input('student_id') ?: $studentList->first()['id'] ?? 0);

        $selectedStudent = $selectedStudentId
            ? Student::query()->find($selectedStudentId)
            : null;

        $selectedEnrollment = $selectedStudentId
            ? Enrollment::query()
                ->with(['gradeLevel:id,name', 'section:id,name'])
                ->where('student_id', $selectedStudentId)
                ->where('academic_year_id', $selectedAcademicYearId)
                ->first()
            : null;

        $effectiveGradeLevelId = $selectedGradeLevelId ?: $selectedEnrollment?->grade_level_id;

        $existingRecords = collect();
        if ($selectedStudentId) {
            $existingRecords = RemedialRecord::query()
                ->with('subject:id,subject_name')
                ->where('student_id', $selectedStudentId)
                ->where('academic_year_id', $selectedAcademicYearId)
                ->get()
                ->keyBy('subject_id');
        }

        $failedGradeMap = collect();
        if ($selectedEnrollment) {
            $failedGrades = FinalGrade::query()
                ->with('subjectAssignment.teacherSubject.subject:id,subject_name')
                ->where('enrollment_id', $selectedEnrollment->id)
                ->whereIn('quarter', ['4', 'final'])
                ->where('grade', '<', 75)
                ->get();

            $failedGradeMap = $failedGrades
                ->mapWithKeys(function (FinalGrade $grade) {
                    $subject = $grade->subjectAssignment?->teacherSubject?->subject;
                    if (! $subject) {
                        return [];
                    }

                    return [$subject->id => (float) $grade->grade];
                });
        }

        $remedialRows = collect();
        if ($effectiveGradeLevelId) {
            $subjects = Subject::query()
                ->where('grade_level_id', $effectiveGradeLevelId)
                ->orderBy('subject_name')
                ->get(['id', 'subject_name']);

            $remedialRows = $subjects
                ->map(function (Subject $subject) use ($existingRecords, $failedGradeMap) {
                    $record = $existingRecords->get($subject->id);

                    $finalRating = $record
                        ? (float) $record->final_rating
                        : ($failedGradeMap[$subject->id] ?? null);

                    $remedialMark = $record ? (float) $record->remedial_class_mark : null;
                    $recomputed = $record
                        ? (float) $record->recomputed_final_grade
                        : ($finalRating !== null && $remedialMark !== null
                            ? round(($finalRating + $remedialMark) / 2, 2)
                            : null);

                    return [
                        'record_id' => $record?->id,
                        'subject_id' => $subject->id,
                        'subject_name' => $subject->subject_name,
                        'final_rating' => $finalRating,
                        'remedial_class_mark' => $remedialMark,
                        'recomputed_final_grade' => $recomputed,
                        'status' => $record?->status
                            ?? ($recomputed !== null
                                ? ($recomputed >= 75 ? 'passed' : 'failed')
                                : 'for_encoding'),
                    ];
                })
                ->filter(function (array $row) {
                    if ($row['record_id']) {
                        return true;
                    }

                    return $row['final_rating'] !== null && $row['final_rating'] < 75;
                })
                ->values();
        }

        $recentEncodings = RemedialRecord::query()
            ->with([
                'student:id,lrn,first_name,last_name',
                'academicYear:id,name',
            ])
            ->latest('updated_at')
            ->get()
            ->groupBy(fn (RemedialRecord $record) => "{$record->student_id}-{$record->academic_year_id}")
            ->map(function ($group) {
                /** @var RemedialRecord $latest */
                $latest = $group->sortByDesc('updated_at')->first();
                $hasFailed = $group->contains(fn (RemedialRecord $record) => $record->status === 'failed');

                return [
                    'student_name' => trim("{$latest->student?->first_name} {$latest->student?->last_name}"),
                    'lrn' => $latest->student?->lrn,
                    'school_year' => $latest->academicYear?->name,
                    'updated_at' => $latest->updated_at?->toDateString(),
                    'status' => $hasFailed ? 'Draft' : 'Submitted',
                ];
            })
            ->sortByDesc('updated_at')
            ->take(10)
            ->values();

        $overallResult = 'No Remedial Subjects';
        if ($remedialRows->count() > 0) {
            $overallResult = $remedialRows->every(fn ($row) => $row['status'] === 'passed')
                ? 'Passed'
                : 'For Encoding';
        }

        return Inertia::render('registrar/remedial-entry/index', [
            'academic_years' => $academicYears,
            'grade_levels' => $gradeLevels,
            'students' => $studentList,
            'selected_student' => $selectedStudent ? [
                'id' => $selectedStudent->id,
                'name' => trim("{$selectedStudent->first_name} {$selectedStudent->last_name}"),
                'lrn' => $selectedStudent->lrn,
                'grade_and_section' => $selectedEnrollment?->gradeLevel?->name && $selectedEnrollment?->section?->name
                    ? "{$selectedEnrollment->gradeLevel->name} - {$selectedEnrollment->section->name}"
                    : 'Unassigned',
                'overall_result' => $overallResult,
            ] : null,
            'remedial_rows' => $remedialRows,
            'recent_encodings' => $recentEncodings,
            'filters' => [
                'academic_year_id' => $selectedAcademicYearId ?: null,
                'grade_level_id' => $selectedGradeLevelId,
                'search' => $search ?: null,
                'student_id' => $selectedStudentId ?: null,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'student_id' => 'required|exists:students,id',
            'save_mode' => 'required|string|in:draft,submitted',
            'records' => 'required|array|min:1',
            'records.*.subject_id' => 'required|exists:subjects,id',
            'records.*.final_rating' => 'nullable|numeric|min:0|max:100',
            'records.*.remedial_class_mark' => 'nullable|numeric|min:0|max:100',
        ]);

        $written = 0;
        $hasFailed = false;

        foreach ($validated['records'] as $record) {
            $finalRating = array_key_exists('final_rating', $record) && $record['final_rating'] !== null
                ? (float) $record['final_rating']
                : null;

            $remedialMark = array_key_exists('remedial_class_mark', $record) && $record['remedial_class_mark'] !== null
                ? (float) $record['remedial_class_mark']
                : null;

            if ($finalRating === null || $remedialMark === null) {
                continue;
            }

            $recomputed = round(($finalRating + $remedialMark) / 2, 2);
            $status = $recomputed >= 75 ? 'passed' : 'failed';

            RemedialRecord::query()->updateOrCreate(
                [
                    'student_id' => $validated['student_id'],
                    'subject_id' => $record['subject_id'],
                    'academic_year_id' => $validated['academic_year_id'],
                ],
                [
                    'final_rating' => $finalRating,
                    'remedial_class_mark' => $remedialMark,
                    'recomputed_final_grade' => $recomputed,
                    'status' => $status,
                ]
            );

            $written++;

            if ($status === 'failed') {
                $hasFailed = true;
            }
        }

        if ($written === 0) {
            return back()->with('error', 'No remedial rows were saved. Please enter at least one complete row.');
        }

        Student::query()
            ->whereKey($validated['student_id'])
            ->update([
                'is_for_remedial' => $validated['save_mode'] === 'draft' || $hasFailed,
            ]);

        $message = $validated['save_mode'] === 'submitted'
            ? 'Remedial results submitted.'
            : 'Remedial draft saved.';

        return back()->with('success', $message);
    }
}
