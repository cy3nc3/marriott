<?php

namespace App\Http\Middleware;

use App\Models\AcademicYear;
use App\Models\User;
use App\Services\AnnouncementNotificationService;
use App\Services\HandheldDeviceDetector;
use Illuminate\Http\Request;
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

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'active_academic_year' => fn () => $this->resolveActiveAcademicYear(),
            'flash' => [
                'login_welcome_toast' => fn () => $request->session()->get('login_welcome_toast'),
                'saved_account_login' => fn () => $request->session()->get('saved_account_login'),
            ],
            'notifications' => $user instanceof User
                ? $this->announcementNotificationService->buildPayload($user)
                : [
                    'announcements' => [],
                    'unread_count' => 0,
                ],
            'permissions' => $user instanceof User ? \App\Models\Permission::where('role', $user->role->value)->pluck('access_level', 'feature')->toArray() : [],
            'ui' => [
                'is_handheld' => $this->handheldDeviceDetector->isHandheldRequest($request),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
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
