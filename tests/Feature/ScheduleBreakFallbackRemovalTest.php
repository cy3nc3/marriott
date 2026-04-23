<?php

use App\Models\Student;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('teacher schedule does not include hardcoded break fallbacks', function (): void {
    $teacher = User::factory()->teacher()->create();

    $this->actingAs($teacher)
        ->get(route('teacher.schedule'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('break_items', []));
});

test('student schedule does not include hardcoded break fallbacks', function (): void {
    $studentUser = User::factory()->student()->create();

    Student::query()->create([
        'user_id' => $studentUser->id,
        'lrn' => 'LRN-'.str_pad((string) random_int(1, 999999999), 9, '0', STR_PAD_LEFT),
        'first_name' => 'Test',
        'last_name' => 'Student',
    ]);

    $this->actingAs($studentUser)
        ->get(route('student.schedule'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('break_items', []));
});

test('parent schedule does not include hardcoded break fallbacks', function (): void {
    $parent = User::factory()->parent()->create();

    $this->actingAs($parent)
        ->get(route('parent.schedule'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('break_items', []));
});
