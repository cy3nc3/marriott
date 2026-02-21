<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ConductRating;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class GradesController extends Controller
{
    public function index(): Response
    {
        $student = $this->resolveStudent(auth()->user());
        $enrollment = $student ? $this->resolveCurrentEnrollment($student) : null;

        $subjectRows = collect();
        $conductRows = collect([
            ['core_value' => 'Maka-Diyos', 'q1' => '-', 'q2' => '-', 'q3' => '-', 'q4' => '-'],
            ['core_value' => 'Makatao', 'q1' => '-', 'q2' => '-', 'q3' => '-', 'q4' => '-'],
            ['core_value' => 'Makakalikasan', 'q1' => '-', 'q2' => '-', 'q3' => '-', 'q4' => '-'],
            ['core_value' => 'Makabansa', 'q1' => '-', 'q2' => '-', 'q3' => '-', 'q4' => '-'],
        ]);

        $generalAverage = null;
        $trendText = 'No quarter trend yet.';
        $context = [
            'school_year' => null,
            'adviser_name' => null,
            'adviser_remarks' => null,
        ];

        if ($enrollment) {
            $context['school_year'] = $enrollment->academicYear?->name;
            $context['adviser_name'] = $enrollment->section?->adviser
                ? trim("{$enrollment->section->adviser->first_name} {$enrollment->section->adviser->last_name}")
                : null;

            $finalGrades = FinalGrade::query()
                ->with('subjectAssignment.teacherSubject.subject:id,subject_name')
                ->where('enrollment_id', $enrollment->id)
                ->whereIn('quarter', ['1', '2', '3', '4'])
                ->get();

            $subjectRows = $this->buildSubjectRows($finalGrades);
            $generalAverage = $this->calculateGeneralAverage($subjectRows);

            $quarterAverages = $this->calculateQuarterAverages($subjectRows);
            $trendText = $this->resolveTrendText($quarterAverages);

            $conductRatings = ConductRating::query()
                ->where('enrollment_id', $enrollment->id)
                ->whereIn('quarter', ['1', '2', '3', '4'])
                ->orderBy('quarter')
                ->get()
                ->keyBy('quarter');

            $conductRows = collect([
                [
                    'core_value' => 'Maka-Diyos',
                    'q1' => $conductRatings->get('1')?->maka_diyos ?: '-',
                    'q2' => $conductRatings->get('2')?->maka_diyos ?: '-',
                    'q3' => $conductRatings->get('3')?->maka_diyos ?: '-',
                    'q4' => $conductRatings->get('4')?->maka_diyos ?: '-',
                ],
                [
                    'core_value' => 'Makatao',
                    'q1' => $conductRatings->get('1')?->makatao ?: '-',
                    'q2' => $conductRatings->get('2')?->makatao ?: '-',
                    'q3' => $conductRatings->get('3')?->makatao ?: '-',
                    'q4' => $conductRatings->get('4')?->makatao ?: '-',
                ],
                [
                    'core_value' => 'Makakalikasan',
                    'q1' => $conductRatings->get('1')?->makakalikasan ?: '-',
                    'q2' => $conductRatings->get('2')?->makakalikasan ?: '-',
                    'q3' => $conductRatings->get('3')?->makakalikasan ?: '-',
                    'q4' => $conductRatings->get('4')?->makakalikasan ?: '-',
                ],
                [
                    'core_value' => 'Makabansa',
                    'q1' => $conductRatings->get('1')?->makabansa ?: '-',
                    'q2' => $conductRatings->get('2')?->makabansa ?: '-',
                    'q3' => $conductRatings->get('3')?->makabansa ?: '-',
                    'q4' => $conductRatings->get('4')?->makabansa ?: '-',
                ],
            ]);

            $context['adviser_remarks'] = collect($conductRatings->values())
                ->sortByDesc('quarter')
                ->first(fn (ConductRating $conductRating) => filled($conductRating->remarks))
                ?->remarks;
        }

        return Inertia::render('student/grades/index', [
            'summary' => [
                'general_average' => $generalAverage,
                'trend_text' => $trendText,
            ],
            'context' => $context,
            'subject_rows' => $subjectRows,
            'conduct_rows' => $conductRows,
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

    private function resolveCurrentEnrollment(Student $student): ?Enrollment
    {
        $activeYearId = AcademicYear::query()
            ->where('status', 'ongoing')
            ->value('id');

        if ($activeYearId) {
            $activeEnrollment = Enrollment::query()
                ->with(['academicYear:id,name', 'section:id,adviser_id', 'section.adviser:id,first_name,last_name'])
                ->where('student_id', $student->id)
                ->where('academic_year_id', $activeYearId)
                ->where('status', 'enrolled')
                ->first();

            if ($activeEnrollment) {
                return $activeEnrollment;
            }
        }

        return Enrollment::query()
            ->with(['academicYear:id,name', 'section:id,adviser_id', 'section.adviser:id,first_name,last_name'])
            ->where('student_id', $student->id)
            ->where('status', 'enrolled')
            ->latest('id')
            ->first();
    }

    private function buildSubjectRows(Collection $finalGrades): Collection
    {
        return $finalGrades
            ->map(function (FinalGrade $finalGrade) {
                $subject = $finalGrade->subjectAssignment?->teacherSubject?->subject;
                if (! $subject) {
                    return null;
                }

                return [
                    'subject_id' => (int) $subject->id,
                    'subject_name' => $subject->subject_name,
                    'quarter' => (string) $finalGrade->quarter,
                    'grade' => (float) $finalGrade->grade,
                ];
            })
            ->filter()
            ->groupBy('subject_id')
            ->map(function (Collection $groupedRows) {
                $subjectName = (string) ($groupedRows->first()['subject_name'] ?? 'Subject');
                $gradeByQuarter = $groupedRows
                    ->mapWithKeys(function (array $gradeRow) {
                        return [$gradeRow['quarter'] => $gradeRow['grade']];
                    });

                $quarterValues = collect(['1', '2', '3', '4'])
                    ->map(function (string $quarter) use ($gradeByQuarter) {
                        return $gradeByQuarter->has($quarter)
                            ? $this->formatGrade((float) $gradeByQuarter->get($quarter))
                            : '-';
                    });

                $numericQuarterValues = collect(['1', '2', '3', '4'])
                    ->filter(function (string $quarter) use ($gradeByQuarter) {
                        return $gradeByQuarter->has($quarter);
                    })
                    ->map(function (string $quarter) use ($gradeByQuarter) {
                        return (float) $gradeByQuarter->get($quarter);
                    });

                $finalGrade = $numericQuarterValues->isNotEmpty()
                    ? $this->formatGrade(round((float) $numericQuarterValues->avg(), 2))
                    : '-';

                return [
                    'subject' => $subjectName,
                    'q1' => $quarterValues->get(0),
                    'q2' => $quarterValues->get(1),
                    'q3' => $quarterValues->get(2),
                    'q4' => $quarterValues->get(3),
                    'final' => $finalGrade,
                ];
            })
            ->sortBy('subject')
            ->values();
    }

    private function calculateGeneralAverage(Collection $subjectRows): ?string
    {
        $finalValues = $subjectRows
            ->map(function (array $subjectRow) {
                return $subjectRow['final'] !== '-'
                    ? (float) $subjectRow['final']
                    : null;
            })
            ->filter(fn (?float $value) => $value !== null)
            ->values();

        if ($finalValues->isEmpty()) {
            return null;
        }

        return $this->formatGrade(round((float) $finalValues->avg(), 2));
    }

    private function calculateQuarterAverages(Collection $subjectRows): Collection
    {
        return collect(['q1', 'q2', 'q3', 'q4'])
            ->mapWithKeys(function (string $quarterKey) use ($subjectRows) {
                $values = $subjectRows
                    ->map(function (array $subjectRow) use ($quarterKey) {
                        return $subjectRow[$quarterKey] !== '-'
                            ? (float) $subjectRow[$quarterKey]
                            : null;
                    })
                    ->filter(fn (?float $value) => $value !== null)
                    ->values();

                $average = $values->isNotEmpty()
                    ? round((float) $values->avg(), 2)
                    : null;

                return [$quarterKey => $average];
            });
    }

    private function resolveTrendText(Collection $quarterAverages): string
    {
        $available = $quarterAverages
            ->filter(fn (?float $value) => $value !== null)
            ->values();

        if ($available->count() < 2) {
            return 'No previous quarter comparison yet.';
        }

        $latest = (float) $available->last();
        $previous = (float) $available->get($available->count() - 2);
        $difference = round($latest - $previous, 2);

        if ($difference > 0) {
            return "+{$this->formatGrade($difference)} compared to previous quarter";
        }

        if ($difference < 0) {
            $absoluteDifference = abs($difference);

            return "-{$this->formatGrade($absoluteDifference)} compared to previous quarter";
        }

        return 'No change from previous quarter.';
    }

    private function formatGrade(float $grade): string
    {
        return number_format($grade, 2, '.', '');
    }
}
