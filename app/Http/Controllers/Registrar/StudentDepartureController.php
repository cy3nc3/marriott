<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Http\Requests\Registrar\StoreStudentDepartureRequest;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\StudentDeparture;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentDepartureController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $activeAcademicYearId = AcademicYear::query()
            ->where('status', 'ongoing')
            ->value('id');

        $studentLookup = Student::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('lrn', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(20)
            ->get(['id', 'lrn', 'first_name', 'last_name'])
            ->map(function (Student $student): array {
                return [
                    'id' => (int) $student->id,
                    'lrn' => $student->lrn,
                    'name' => trim("{$student->first_name} {$student->last_name}"),
                ];
            })
            ->values();

        $selectedStudentId = (int) ($request->input('student_id') ?: ($studentLookup->first()['id'] ?? 0));

        $selectedStudent = $selectedStudentId > 0
            ? Student::query()
                ->with('user:id,is_active,access_expires_at')
                ->find($selectedStudentId)
            : null;

        $selectedEnrollment = null;
        if ($selectedStudent) {
            $selectedEnrollment = Enrollment::query()
                ->with([
                    'academicYear:id,name,start_date,end_date',
                    'gradeLevel:id,name',
                    'section:id,name',
                ])
                ->where('student_id', $selectedStudent->id)
                ->when($activeAcademicYearId, function ($query) use ($activeAcademicYearId) {
                    $query->where('academic_year_id', $activeAcademicYearId);
                })
                ->orderByRaw("CASE WHEN status = 'enrolled' THEN 0 ELSE 1 END")
                ->orderByDesc('id')
                ->first();

            if (! $selectedEnrollment) {
                $selectedEnrollment = Enrollment::query()
                    ->with([
                        'academicYear:id,name,start_date,end_date',
                        'gradeLevel:id,name',
                        'section:id,name',
                    ])
                    ->where('student_id', $selectedStudent->id)
                    ->orderByDesc('id')
                    ->first();
            }
        }

        $selectedStudentPayload = null;
        if ($selectedStudent) {
            $gradeAndSection = $selectedEnrollment?->gradeLevel?->name
                ? trim(($selectedEnrollment?->gradeLevel?->name ?: '').($selectedEnrollment?->section?->name ? " - {$selectedEnrollment->section->name}" : ''))
                : 'Unassigned';

            $selectedStudentPayload = [
                'id' => (int) $selectedStudent->id,
                'name' => trim("{$selectedStudent->first_name} {$selectedStudent->last_name}"),
                'lrn' => $selectedStudent->lrn,
                'grade_and_section' => $gradeAndSection,
                'enrollment_status' => $selectedEnrollment?->status,
                'academic_year' => $selectedEnrollment?->academicYear?->name,
                'enrollment_id' => $selectedEnrollment?->id,
                'account_expires_at' => $selectedStudent->user?->access_expires_at?->toDateString(),
            ];
        }

        $recentDepartures = StudentDeparture::query()
            ->with([
                'student:id,first_name,last_name,lrn',
                'academicYear:id,name',
                'processedBy:id,first_name,last_name',
            ])
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(function (StudentDeparture $departure): array {
                return [
                    'id' => (int) $departure->id,
                    'student_name' => trim("{$departure->student?->first_name} {$departure->student?->last_name}"),
                    'lrn' => $departure->student?->lrn,
                    'school_year' => $departure->academicYear?->name,
                    'reason' => $departure->reason === 'transfer_out' ? 'Transfer Out' : 'Dropped Out',
                    'effective_date' => $departure->effective_date?->format('m/d/Y'),
                    'account_expires_at' => $departure->account_expires_at?->format('m/d/Y'),
                    'processed_by' => trim("{$departure->processedBy?->first_name} {$departure->processedBy?->last_name}"),
                ];
            })
            ->values();

        return Inertia::render('registrar/student-departure/index', [
            'student_lookup' => $studentLookup,
            'selected_student' => $selectedStudentPayload,
            'departure_form_defaults' => [
                'reason' => 'transfer_out',
                'effective_date' => now()->toDateString(),
                'remarks' => '',
            ],
            'recent_departures' => $recentDepartures,
            'filters' => [
                'search' => $search !== '' ? $search : null,
                'student_id' => $selectedStudentId > 0 ? $selectedStudentId : null,
            ],
        ]);
    }

    public function store(StoreStudentDepartureRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $enrollment = Enrollment::query()
            ->with('academicYear:id,name,start_date,end_date')
            ->whereKey($validated['enrollment_id'])
            ->where('student_id', $validated['student_id'])
            ->first();

        if (! $enrollment) {
            return back()->with('error', 'Selected enrollment record does not match the selected student.');
        }

        $reason = $validated['reason'];
        $mappedEnrollmentStatus = $reason === 'transfer_out' ? 'transferred_out' : 'dropped_out';
        $effectiveDate = Carbon::parse($validated['effective_date'])->startOfDay();

        $nextAcademicYear = null;
        if ($enrollment->academicYear) {
            $nextAcademicYear = AcademicYear::query()
                ->where('start_date', '>', $enrollment->academicYear->start_date)
                ->orderBy('start_date')
                ->first();
        }

        $accountExpiresAt = $nextAcademicYear
            ? Carbon::parse($nextAcademicYear->start_date)->startOfDay()
            : ($enrollment->academicYear?->end_date
                ? Carbon::parse($enrollment->academicYear->end_date)->addDay()->startOfDay()
                : $effectiveDate->copy()->addDay());

        StudentDeparture::query()->create([
            'student_id' => $validated['student_id'],
            'enrollment_id' => $enrollment->id,
            'academic_year_id' => $enrollment->academic_year_id,
            'reason' => $reason,
            'effective_date' => $effectiveDate->toDateString(),
            'remarks' => $validated['remarks'] ?: null,
            'processed_by' => auth()->id(),
            'account_expires_at' => $accountExpiresAt,
        ]);

        $enrollment->update([
            'status' => $mappedEnrollmentStatus,
        ]);

        $student = Student::query()
            ->with('user')
            ->find($validated['student_id']);

        if ($student?->user) {
            $student->user->update([
                'is_active' => true,
                'access_expires_at' => $accountExpiresAt,
            ]);
        }

        return back()->with('success', 'Student departure processed.');
    }
}
