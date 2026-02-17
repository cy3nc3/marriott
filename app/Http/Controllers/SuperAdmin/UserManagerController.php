<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserManagerController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->when($request->input('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($request->input('role') && $request->input('role') !== 'all', function ($query, $role) {
                $query->where('role', $role);
            })
            ->orderBy('name')
            ->get();

        return Inertia::render('super_admin/user-manager/index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'birthday' => 'required|date',
            'role' => 'required|string',
        ]);

        $firstName = strtolower(explode(' ', trim($validated['first_name']))[0]);
        $firstName = preg_replace('/[^a-z0-9]/', '', $firstName);
        
        $lastName = strtolower(str_replace(' ', '', trim($validated['last_name'])));
        $lastName = preg_replace('/[^a-z0-9]/', '', $lastName);
        
        $email = "{$firstName}.{$lastName}@marriott.edu";

        // Handle duplicate emails
        $originalEmail = $email;
        $count = 1;
        while (User::where('email', $email)->exists()) {
            $email = Str::before($originalEmail, '@') . $count . '@marriott.edu';
            $count++;
        }

        $password = date('Ymd', strtotime($validated['birthday']));

        User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'email' => $email,
            'birthday' => $validated['birthday'],
            'role' => $validated['role'],
            'password' => Hash::make($password),
        ]);

        return back()->with('success', 'User account created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'birthday' => 'required|date',
            'role' => 'required|string',
        ]);

        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'birthday' => $validated['birthday'],
            'role' => $validated['role'],
        ]);

        return back()->with('success', 'User account updated successfully.');
    }

    public function resetPassword(User $user): RedirectResponse
    {
        if (!$user->birthday) {
            return back()->with('error', 'User birthday is not set. Cannot auto-generate password.');
        }

        $password = date('Ymd', strtotime($user->birthday));
        $user->update([
            'password' => Hash::make($password),
        ]);

        return back()->with('success', 'Password reset to default (birthday) successfully.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $user->update([
            'is_active' => !$user->is_active,
        ]);

        $status = $user->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "User account {$status} successfully.");
    }
}
