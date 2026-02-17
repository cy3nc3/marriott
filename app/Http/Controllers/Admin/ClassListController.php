<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassListController extends Controller
{
    public function index(Request $request): Response
    {
        $activeYear = AcademicYear::where('status', 'ongoing')->first() 
              ?? AcademicYear::where('status', '!=', 'completed')->first()
              ?? AcademicYear::orderBy('end_date', 'desc')->first();

        if (! $activeYear) {
            return Inertia::render('admin/class-lists/index', [
                'activeYear' => null,
                'gradeLevels' => [],
            ]);
        }

        $gradeLevels = GradeLevel::with(['sections' => function ($query) use ($activeYear) {
            $query->where('academic_year_id', $activeYear->id)
                ->withCount(['enrollments' => fn($q) => $q->where('status', 'enrolled')])
                ->with(['enrollments' => fn($q) => $q->where('status', 'enrolled')->with('student')]);
        }])->orderBy('level_order')->get();

        return Inertia::render('admin/class-lists/index', [
            'activeYear' => $activeYear,
            'gradeLevels' => $gradeLevels,
        ]);
    }
}
