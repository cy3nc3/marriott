<?php

use App\Enums\UserRole;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

const MOBILE_USER_AGENT = 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148';

beforeEach(function (): void {
    $this->withoutVite();
});

test('mobile request blocks finance cashier panel and returns desktop required page', function () {
    $user = User::factory()->finance()->create();

    $this->actingAs($user)
        ->withHeader('User-Agent', MOBILE_USER_AGENT)
        ->get('/finance/cashier-panel')
        ->assertStatus(403)
        ->assertInertia(fn (Assert $page) => $page
            ->component('mobile/desktop-required')
            ->where('role', UserRole::FINANCE->value)
            ->where('requested_path', '/finance/cashier-panel')
        );
});

test('mobile request allows finance student ledgers read only page', function () {
    $user = User::factory()->finance()->create();

    $this->actingAs($user)
        ->withHeader('User-Agent', MOBILE_USER_AGENT)
        ->get('/finance/student-ledgers')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/student-ledgers/index')
        );
});

test('mobile request blocks non get finance cashier action', function () {
    $user = User::factory()->finance()->create();

    $this->actingAs($user)
        ->withHeader('User-Agent', MOBILE_USER_AGENT)
        ->post('/finance/cashier-panel/transactions', [])
        ->assertStatus(403);
});

test('mobile request allows teacher grading mutations', function () {
    $user = User::factory()->teacher()->create();

    $response = $this->actingAs($user)
        ->withHeader('User-Agent', MOBILE_USER_AGENT)
        ->post('/teacher/grading-sheet/rubric', []);

    expect($response->getStatusCode())->not->toBe(403);
});

test('mobile request blocks admin grade verification mutation and allows list page', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->withHeader('User-Agent', MOBILE_USER_AGENT)
        ->get('/admin/grade-verification')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/grade-verification/index')
        );

    $this->actingAs($user)
        ->withHeader('User-Agent', MOBILE_USER_AGENT)
        ->post('/admin/grade-verification/deadline', [])
        ->assertStatus(403);
});

test('shared inertia props include handheld ui flag', function () {
    $user = User::factory()->student()->create();

    $this->actingAs($user)
        ->withHeader('User-Agent', MOBILE_USER_AGENT)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('ui.is_handheld', true)
        );
});
