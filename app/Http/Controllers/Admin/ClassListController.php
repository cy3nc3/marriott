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
        $activeYear = AcademicYear::where('status', '!=', 'completed')->first();

        if (! $activeYear) {
            return Inertia::render('admin/class-lists/index', [
                'activeYear' => null,
                'gradeLevels' => [],
            ]);
        }

        $gradeLevels = GradeLevel::with(['sections' => function ($query) use ($activeYear) {
            $query->where('academic_year_id', $activeYear->id)
                ->withCount('enrollments')
                ->with(['enrollments.student']);
        }])->orderBy('level_order')->get();

        return Inertia::render('admin/class-lists/index', [
            'activeYear' => $activeYear,
            'gradeLevels' => $gradeLevels,
        ]);
    }
}
