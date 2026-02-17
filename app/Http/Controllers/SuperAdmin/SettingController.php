<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');

        return Inertia::render('super_admin/system-settings/index', [
            'settings' => $settings,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_name' => 'nullable|string',
            'school_id' => 'nullable|string',
            'address' => 'nullable|string',
            'maintenance_mode' => 'boolean',
            'parent_portal' => 'boolean',
            // Files are handled separately or via separate endpoint? 
            // Better to handle file uploads here if sent as multipart/form-data
        ]);

        foreach ($request->except(['logo', 'header']) as $key => $value) {
            Setting::set($key, $value, 'system');
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('public/settings');
            Setting::set('logo', Storage::url($path), 'appearance');
        }

        if ($request->hasFile('header')) {
            $path = $request->file('header')->store('public/settings');
            Setting::set('header', Storage::url($path), 'appearance');
        }

        return back()->with('success', 'Settings updated successfully.');
    }
}
