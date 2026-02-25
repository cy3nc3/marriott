<?php

namespace App\Http\Controllers;

use App\Http\Requests\Announcements\StoreAnnouncementEventResponseRequest;
use App\Models\Announcement;
use App\Models\AnnouncementAttachment;
use App\Models\User;
use App\Services\AnnouncementNotificationService;
use App\Services\AnnouncementResponseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnnouncementNotificationController extends Controller
{
    public function __construct(
        private AnnouncementNotificationService $announcementNotificationService,
        private AnnouncementResponseService $announcementResponseService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', 'all');

        if (! in_array($status, ['all', 'unread', 'read'], true)) {
            $status = 'all';
        }

        $announcementsQuery = $this->announcementNotificationService
            ->visibleAnnouncementsForUserQuery($user)
            ->with([
                'reads' => function ($query) use ($user): void {
                    $query
                        ->where('user_id', $user->id)
                        ->select(['id', 'announcement_id', 'user_id', 'read_at']);
                },
                'eventResponses' => function ($query) use ($user): void {
                    $query
                        ->where('user_id', $user->id)
                        ->select([
                            'id',
                            'announcement_id',
                            'user_id',
                            'response',
                            'responded_at',
                        ]);
                },
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->when($status === 'unread', function ($query) use ($user): void {
                $query->whereDoesntHave('reads', function ($readQuery) use ($user): void {
                    $readQuery->where('user_id', $user->id);
                });
            })
            ->when($status === 'read', function ($query) use ($user): void {
                $query->whereHas('reads', function ($readQuery) use ($user): void {
                    $readQuery->where('user_id', $user->id);
                });
            })
            ->latest();

        $announcements = $announcementsQuery
            ->paginate(15)
            ->withQueryString()
            ->through(function (Announcement $announcement) use ($user): array {
                $viewerResponseStatus = $this->announcementNotificationService
                    ->resolveViewerResponseStatus($announcement, $user);

                return [
                    'id' => (int) $announcement->id,
                    'title' => (string) $announcement->title,
                    'content_preview' => Str::limit(trim((string) $announcement->content), 180),
                    'created_at' => $announcement->created_at?->toIso8601String(),
                    'publish_at' => $announcement->publish_at?->toIso8601String(),
                    'expires_at' => $announcement->expires_at?->toIso8601String(),
                    'type' => (string) ($announcement->type ?? Announcement::TYPE_NOTICE),
                    'response_mode' => (string) ($announcement->response_mode ?? Announcement::RESPONSE_MODE_NONE),
                    'event_starts_at' => $announcement->event_starts_at?->toIso8601String(),
                    'event_ends_at' => $announcement->event_ends_at?->toIso8601String(),
                    'response_deadline_at' => $announcement->response_deadline_at?->toIso8601String(),
                    'is_cancelled' => $announcement->cancelled_at !== null,
                    'cancelled_at' => $announcement->cancelled_at?->toIso8601String(),
                    'cancel_reason' => $announcement->cancel_reason,
                    'is_read' => $announcement->reads->isNotEmpty(),
                    'viewer_response_status' => $viewerResponseStatus,
                    'requires_action' => $this->announcementNotificationService->requiresAction(
                        $user,
                        $announcement,
                        $viewerResponseStatus
                    ),
                    'show_url' => route('notifications.announcements.show', [
                        'announcement' => $announcement->id,
                    ]),
                ];
            });

        return Inertia::render('notifications/inbox/index', [
            'announcements' => $announcements,
            'filters' => [
                'search' => $search !== '' ? $search : null,
                'status' => $status,
            ],
        ]);
    }

    public function show(Request $request, Announcement $announcement): Response
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        if (! $this->announcementNotificationService->isVisibleToUser($user, $announcement)) {
            abort(403);
        }

        $this->announcementNotificationService->markAsRead($user, $announcement);

        $announcement->load([
            'attachments:id,announcement_id,original_name,mime_type,file_size,stored_path',
            'eventResponses' => function ($query) use ($user): void {
                $query
                    ->where('user_id', $user->id)
                    ->select([
                        'id',
                        'announcement_id',
                        'user_id',
                        'response',
                        'responded_at',
                        'note',
                    ]);
            },
        ]);

        $viewerResponseStatus = $this->announcementNotificationService
            ->resolveViewerResponseStatus($announcement, $user);

        return Inertia::render('notifications/announcements/show', [
            'announcement' => [
                'id' => (int) $announcement->id,
                'title' => (string) $announcement->title,
                'content' => (string) $announcement->content,
                'created_at' => $announcement->created_at?->toIso8601String(),
                'publish_at' => $announcement->publish_at?->toIso8601String(),
                'event_starts_at' => $announcement->event_starts_at?->toIso8601String(),
                'event_ends_at' => $announcement->event_ends_at?->toIso8601String(),
                'response_deadline_at' => $announcement->response_deadline_at?->toIso8601String(),
                'expires_at' => $announcement->expires_at?->toIso8601String(),
                'type' => (string) ($announcement->type ?? Announcement::TYPE_NOTICE),
                'response_mode' => (string) ($announcement->response_mode ?? Announcement::RESPONSE_MODE_NONE),
                'is_cancelled' => $announcement->cancelled_at !== null,
                'cancelled_at' => $announcement->cancelled_at?->toIso8601String(),
                'cancel_reason' => $announcement->cancel_reason,
                'viewer_response_status' => $viewerResponseStatus,
                'viewer_responded_at' => $announcement->eventResponses->first()?->responded_at?->toIso8601String(),
                'viewer_response_note' => $announcement->eventResponses->first()?->note,
                'requires_action' => $this->announcementNotificationService->requiresAction(
                    $user,
                    $announcement,
                    $viewerResponseStatus
                ),
                'action_urls' => [
                    'acknowledge' => route('notifications.announcements.acknowledge', [
                        'announcement' => $announcement->id,
                    ]),
                    'respond' => route('notifications.announcements.respond', [
                        'announcement' => $announcement->id,
                    ]),
                ],
                'attachments' => $announcement->attachments
                    ->map(function (AnnouncementAttachment $attachment) use ($announcement): array {
                        return [
                            'id' => (int) $attachment->id,
                            'original_name' => (string) $attachment->original_name,
                            'mime_type' => $attachment->mime_type,
                            'file_size' => (int) $attachment->file_size,
                            'is_image' => str_starts_with((string) $attachment->mime_type, 'image/'),
                            'view_url' => route('notifications.announcements.attachments.show', [
                                'announcement' => $announcement->id,
                                'attachment' => $attachment->id,
                            ]),
                            'download_url' => route('notifications.announcements.attachments.download', [
                                'announcement' => $announcement->id,
                                'attachment' => $attachment->id,
                            ]),
                        ];
                    })
                    ->values()
                    ->all(),
            ],
        ]);
    }

    public function acknowledge(Request $request, Announcement $announcement): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        if (! $this->announcementNotificationService->isVisibleToUser($user, $announcement)) {
            abort(403);
        }

        $this->announcementResponseService->acknowledge($user, $announcement);

        return back()->with('success', 'Event acknowledged.');
    }

    public function respond(
        StoreAnnouncementEventResponseRequest $request,
        Announcement $announcement
    ): RedirectResponse {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        if (! $this->announcementNotificationService->isVisibleToUser($user, $announcement)) {
            abort(403);
        }

        $validated = $request->validated();

        $this->announcementResponseService->respond(
            $user,
            $announcement,
            (string) $validated['response'],
            $validated['note'] ?? null,
        );

        return back()->with('success', 'RSVP submitted.');
    }

    public function markAsRead(Request $request, Announcement $announcement): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        if (! $this->announcementNotificationService->isVisibleToUser($user, $announcement)) {
            abort(403);
        }

        $this->announcementNotificationService->markAsRead($user, $announcement);

        return back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $this->announcementNotificationService->markAllAsRead($user);

        return back();
    }

    public function showAttachment(Request $request, Announcement $announcement, AnnouncementAttachment $attachment): StreamedResponse
    {
        $this->authorizeAttachmentAccess($request, $announcement, $attachment);

        if (! Storage::disk('local')->exists($attachment->stored_path)) {
            abort(404);
        }

        return Storage::disk('local')->response(
            $attachment->stored_path,
            $attachment->original_name,
            [
                'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            ]
        );
    }

    public function downloadAttachment(Request $request, Announcement $announcement, AnnouncementAttachment $attachment): StreamedResponse
    {
        $this->authorizeAttachmentAccess($request, $announcement, $attachment);

        if (! Storage::disk('local')->exists($attachment->stored_path)) {
            abort(404);
        }

        return Storage::disk('local')->download($attachment->stored_path, $attachment->original_name);
    }

    private function authorizeAttachmentAccess(Request $request, Announcement $announcement, AnnouncementAttachment $attachment): void
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        if (! $this->announcementNotificationService->isVisibleToUser($user, $announcement)) {
            abort(403);
        }

        if ($attachment->announcement_id !== $announcement->id) {
            abort(404);
        }
    }
}
