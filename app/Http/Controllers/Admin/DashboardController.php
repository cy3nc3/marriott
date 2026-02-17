<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TeacherSubject;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $currentYear = AcademicYear::where('status', 'ongoing')->first()
            ?? AcademicYear::where('status', 'upcoming')->orderBy('start_date', 'asc')->first()
            ?? AcademicYear::orderBy('end_date', 'desc')->first();

        // 1. Key Metrics
        $totalStudents = $currentYear
            ? Enrollment::where('academic_year_id', $currentYear->id)
                ->where('status', 'enrolled')
                ->count()
            : 0;

        $totalTeachers = User::where('role', UserRole::TEACHER)->count();

        $activeSections = $currentYear
            ? Section::where('academic_year_id', $currentYear->id)->count()
            : 0;

        $unassignedSubjects = Subject::doesntHave('teachers')->count();

        // 2. Charts Data
        $enrollmentByGrade = [];
        $enrollmentForecast = [];

        if ($currentYear) {
            $enrollmentByGrade = Enrollment::where('enrollments.academic_year_id', $currentYear->id)
                ->where('enrollments.status', 'enrolled')
                ->join('grade_levels', 'enrollments.grade_level_id', '=', 'grade_levels.id')
                ->select('grade_levels.name', 'grade_levels.level_order', DB::raw('count(*) as count'))
                ->groupBy('grade_levels.name', 'grade_levels.level_order')
                ->orderBy('grade_levels.level_order')
                ->get()
                ->map(fn ($item) => [
                    'name' => $item->name,
                    'count' => $item->count,
                ]);

            // Yearly Enrollment Forecast
            $academicYears = AcademicYear::orderBy('start_date', 'asc')->get();
            
            foreach ($academicYears as $year) {
                $count = Enrollment::where('academic_year_id', $year->id)
                    ->where('status', 'enrolled')
                    ->count();

                $enrollmentForecast[] = [
                    'year' => $year->name,
                    'enrollees' => $count > 0 ? $count : null,
                    'isProjected' => false,
                ];
            }

            // Add projection for next year if we have data
            if ($academicYears->isNotEmpty()) {
                $lastYear = $academicYears->last();
                $lastCount = Enrollment::where('academic_year_id', $lastYear->id)->count();
                $nextYearStart = (int) explode('-', $lastYear->name)[1];
                $nextYearName = $nextYearStart . '-' . ($nextYearStart + 1);

                // Simple projection: 15% increase
                $enrollmentForecast[] = [
                    'year' => $nextYearName,
                    'enrollees' => (int) ($lastCount * 1.15),
                    'isProjected' => true,
                ];
            }
        }

        $teacherWorkload = TeacherSubject::select('teacher_id', DB::raw('count(*) as count'))
            ->groupBy('teacher_id')
            ->with('teacher:id,name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'name' => explode(' ', $item->teacher->name)[0],
                'full_name' => $item->teacher->name,
                'count' => $item->count,
            ]);

        return Inertia::render('admin/dashboard', [
            'stats' => [
                'totalStudents' => $totalStudents,
                'totalTeachers' => $totalTeachers,
                'activeSections' => $activeSections,
                'unassignedSubjects' => $unassignedSubjects,
            ],
            'charts' => [
                'enrollmentByGrade' => $enrollmentByGrade,
                'teacherWorkload' => $teacherWorkload,
                'enrollmentForecast' => $enrollmentForecast,
            ],
            'currentYear' => $currentYear,
        ]);
    }
}
