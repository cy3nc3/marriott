<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function index(Request $request): Response
    {
        $announcements = Announcement::query()
            ->with('user:id,name')
            ->latest()
            ->get();

        return Inertia::render('super_admin/announcements/index', [
            'announcements' => $announcements,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'priority' => 'required|string|in:low,normal,high,critical',
            'target_roles' => 'nullable|array',
            'expires_at' => 'nullable|date',
        ]);

        $request->user()->announcements()->create($validated);

        return back()->with('success', 'Announcement posted successfully.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();
        return back()->with('success', 'Announcement removed successfully.');
    }
}
