<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
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
        $enrollmentTrends = [];

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

            $enrollmentTrends = Enrollment::where('academic_year_id', $currentYear->id)
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(fn ($item) => [
                    'date' => $item->date,
                    'count' => $item->count,
                ]);
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
                'enrollmentTrends' => $enrollmentTrends,
            ],
            'currentYear' => $currentYear,
        ]);
    }
}
