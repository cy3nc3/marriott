<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SchoolYearController extends Controller
{
    public function index(): Response
    {
        // Priority: 1. Ongoing year, 2. First Upcoming year, 3. Most recent Completed year
        $currentYear = AcademicYear::where('status', 'ongoing')->first()
            ?? AcademicYear::where('status', 'upcoming')->orderBy('start_date', 'asc')->first()
            ?? AcademicYear::orderBy('end_date', 'desc')->first();

        $nextYearName = null;
        
        // Find the absolute latest year record to determine what the 'next' one should be named
        $latestRecord = AcademicYear::orderBy('end_date', 'desc')->first();
        
        if ($latestRecord) {
            $years = explode('-', $latestRecord->name);
            if (count($years) === 2) {
                $nextStart = (int) $years[0] + 1;
                $nextEnd = (int) $years[1] + 1;
                $nextYearName = "{$nextStart}-{$nextEnd}";
            }
        }

        return Inertia::render('admin/academic-controls/index', [
            'currentYear' => $currentYear,
            'nextYearName' => $nextYearName,
            'allYears' => AcademicYear::orderBy('start_date', 'desc')->get(),
        ]);
    }

    public function updateDates(Request $request, AcademicYear $academicYear)
    {
        $academicYear->update($request->only(['start_date', 'end_date']));

        return back();
    }

    public function initializeNext(Request $request)
    {
        // Simple logic for a student: just create it
        AcademicYear::create([
            'name' => $request->name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'upcoming',
            'current_quarter' => '1',
        ]);

        return back();
    }

    public function simulateOpening(AcademicYear $academicYear)
    {
        // For testing: force the year to ongoing
        $academicYear->update(['status' => 'ongoing']);

        return back();
    }

    public function advanceQuarter(AcademicYear $academicYear)
    {
        $next = (int) $academicYear->current_quarter + 1;

        if ($next <= 4) {
            $academicYear->update(['current_quarter' => (string) $next]);
        } else {
            $academicYear->update(['status' => 'completed']);
        }

        return back();
    }

    public function resetSimulation()
    {
        // Wipe the table for a fresh start
        AcademicYear::truncate();

        return back();
    }
}
