<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpsertTeacherProfileRequest;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TeacherProfileController extends Controller
{
    public function index(): Response
    {
        $teachers = User::query()
            ->where('role', UserRole::TEACHER->value)
            ->where('is_active', true)
            ->with('teacherProfile')
            ->orderBy('name')
            ->get()
            ->map(function (User $teacher): array {
                /** @var TeacherProfile|null $profile */
                $profile = $teacher->teacherProfile;

                return [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'email' => $teacher->email,
                    'profile' => [
                        'qualification_status' => $profile?->qualification_status ?? 'not_qualified',
                        'is_let_passer' => (bool) ($profile?->is_let_passer ?? false),
                        'prc_license_no' => $profile?->prc_license_no,
                        'license_valid_until' => $profile?->license_valid_until?->format('Y-m-d'),
                        'degree' => $profile?->degree,
                        'major' => $profile?->major,
                        'professional_education_units' => $profile?->professional_education_units,
                        'exception_basis' => $profile?->exception_basis,
                        'provisional_until' => $profile?->provisional_until?->format('Y-m-d'),
                        'notes' => $profile?->notes,
                        'eligibility_documents' => $profile?->eligibility_documents ?? [],
                    ],
                ];
            })
            ->values();

        return Inertia::render('admin/teacher-profiles/index', [
            'teachers' => $teachers,
        ]);
    }

    public function upsert(UpsertTeacherProfileRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $documents = collect($validated['retained_documents'] ?? [])
            ->filter(fn ($path) => is_string($path) && str_starts_with($path, 'teacher-documents/'))
            ->filter(fn (string $path) => Storage::disk('local')->exists($path))
            ->values()
            ->all();

        if ($request->hasFile('new_documents')) {
            foreach ($request->file('new_documents') as $file) {
                $path = $file->store('teacher-documents', 'local');
                if ($path) {
                    $documents[] = $path;
                }
            }
        }

        $user->teacherProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'qualification_status' => $validated['qualification_status'],
                'is_let_passer' => (bool) $validated['is_let_passer'],
                'prc_license_no' => $validated['prc_license_no'] ?? null,
                'license_valid_until' => $validated['license_valid_until'] ?? null,
                'degree' => $validated['degree'] ?? null,
                'major' => $validated['major'] ?? null,
                'professional_education_units' => $validated['professional_education_units'] ?? null,
                'exception_basis' => $validated['exception_basis'] ?? null,
                'provisional_until' => $validated['provisional_until'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'eligibility_documents' => $documents,
            ]
        );

        return back()->with('success', 'Teacher profile updated successfully.');
    }

    public function downloadDocument(Request $request, User $user): StreamedResponse
    {
        $documentPath = (string) $request->query('path', '');
        if ($documentPath === '' || ! str_starts_with($documentPath, 'teacher-documents/')) {
            abort(404);
        }

        /** @var TeacherProfile|null $profile */
        $profile = $user->teacherProfile;
        if (! $profile) {
            abort(404);
        }

        $allowedDocuments = collect($profile->eligibility_documents ?? [])
            ->filter(fn ($path) => is_string($path))
            ->values();

        if (! $allowedDocuments->contains($documentPath)) {
            abort(403);
        }

        if (! Storage::disk('local')->exists($documentPath)) {
            abort(404);
        }

        return Storage::disk('local')->download($documentPath, basename($documentPath));
    }
}
