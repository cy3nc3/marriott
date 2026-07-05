<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Permission;
use App\Models\User;
use App\Services\AnnouncementNotificationService;
use App\Services\HandheldDeviceDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(
        private AnnouncementNotificationService $announcementNotificationService,
        private HandheldDeviceDetector $handheldDeviceDetector,
    ) {}

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $effectiveRole = $this->resolveEffectiveRole($request, $user);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'effective_role' => $effectiveRole,
                'view_as_role' => $user instanceof User && $user->role === UserRole::SUPER_ADMIN
                    ? ($effectiveRole !== UserRole::SUPER_ADMIN->value ? $effectiveRole : null)
                    : null,
            ],
            'active_academic_year' => fn () => $this->resolveActiveAcademicYear(),
            'flash' => [
                'login_welcome_toast' => fn () => $request->session()->get('login_welcome_toast'),
                'saved_account_login' => fn () => $request->session()->get('saved_account_login'),
                'assessment_print_url' => fn () => $request->session()->get('assessment_print_url'),
            ],
            'notifications' => $user instanceof User
                ? fn () => $this->announcementNotificationService->buildPayload($user)
                : fn () => [
                    'announcements' => [],
                    'unread_count' => 0,
                ],
            'permissions' => $user instanceof User
                ? fn () => Cache::remember(
                    sprintf('permissions:%s', $effectiveRole),
                    now()->addMinutes(5),
                    fn (): array => Permission::query()
                        ->where('role', $effectiveRole)
                        ->pluck('access_level', 'feature')
                        ->toArray()
                )
                : fn () => [],
            'ui' => [
                'is_handheld' => $this->handheldDeviceDetector->isHandheldRequest($request),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    private function resolveEffectiveRole(Request $request, ?User $user): ?string
    {
        if (! $user instanceof User) {
            return null;
        }

        $authenticatedRole = $user->role->value;
        if ($authenticatedRole !== UserRole::SUPER_ADMIN->value) {
            return $authenticatedRole;
        }

        $viewAsRole = (string) ($request->session()->get('view_as_role') ?? '');
        $allowed = [
            UserRole::ADMIN->value,
            UserRole::REGISTRAR->value,
            UserRole::FINANCE->value,
            UserRole::TEACHER->value,
            UserRole::STUDENT->value,
            UserRole::PARENT->value,
        ];

        if (in_array($viewAsRole, $allowed, true)) {
            return $viewAsRole;
        }

        return $authenticatedRole;
    }

    /**
     * @return array{id: int, name: string, status: string}|null
     */
    private function resolveActiveAcademicYear(): ?array
    {
        $activeAcademicYear = AcademicYear::query()
            ->select(['id', 'name', 'status'])
            ->where('status', 'ongoing')
            ->first()
            ?? AcademicYear::query()
                ->select(['id', 'name', 'status'])
                ->where('status', 'upcoming')
                ->first();

        if (! $activeAcademicYear instanceof AcademicYear) {
            return null;
        }

        return [
            'id' => (int) $activeAcademicYear->id,
            'name' => (string) $activeAcademicYear->name,
            'status' => (string) $activeAcademicYear->status,
        ];
    }
}
