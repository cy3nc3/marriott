<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementAttachment;
use App\Models\User;
use App\Services\AnnouncementNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnnouncementNotificationController extends Controller
{
    public function __construct(private AnnouncementNotificationService $announcementNotificationService) {}

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
        ]);

        return Inertia::render('notifications/announcements/show', [
            'announcement' => [
                'id' => (int) $announcement->id,
                'title' => (string) $announcement->title,
                'content' => (string) $announcement->content,
                'created_at' => $announcement->created_at?->toIso8601String(),
                'expires_at' => $announcement->expires_at?->toIso8601String(),
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
