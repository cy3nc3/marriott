<?php

use App\Models\AcademicYear;
use App\Models\Discount;
use App\Models\Student;
use App\Models\StudentDiscount;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->finance = User::factory()->finance()->create();
    $this->actingAs($this->finance);
});

test('finance discount manager page renders discount and tagged student data', function () {
    $academicYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $student = Student::query()->create([
        'lrn' => '123456789012',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
    ]);

    $discount = Discount::query()->create([
        'name' => 'Academic Scholarship',
        'type' => 'percentage',
        'value' => 100,
    ]);

    StudentDiscount::query()->create([
        'student_id' => $student->id,
        'discount_id' => $discount->id,
        'academic_year_id' => $academicYear->id,
    ]);

    $this->get('/finance/discount-manager')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/discount-manager/index')
            ->has('discount_programs', 1)
            ->where('discount_programs.0.name', 'Academic Scholarship')
            ->where('discount_programs.0.calculation', 'Percentage')
            ->has('tagged_students', 1)
            ->where('tagged_students.0.student_name', 'Juan Dela Cruz')
            ->where('active_academic_year.name', '2025-2026')
        );
});

test('finance can create update and delete discount programs and tag students', function () {
    $academicYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $student = Student::query()->create([
        'lrn' => '098765432109',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
    ]);

    $this->post('/finance/discount-manager', [
        'name' => 'Sibling Discount',
        'type' => 'percentage',
        'value' => 10,
    ])->assertRedirect();

    $discount = Discount::query()->first();

    expect($discount)->not->toBeNull();
    expect($discount->name)->toBe('Sibling Discount');
    expect($discount->type)->toBe('percentage');
    expect((float) $discount->value)->toBe(10.0);

    $this->patch("/finance/discount-manager/{$discount->id}", [
        'name' => 'Sibling Discount Updated',
        'type' => 'fixed',
        'value' => 1500,
    ])->assertRedirect();

    $discount->refresh();

    expect($discount->name)->toBe('Sibling Discount Updated');
    expect($discount->type)->toBe('fixed');
    expect((float) $discount->value)->toBe(1500.0);

    $this->post('/finance/discount-manager/tag-student', [
        'student_id' => $student->id,
        'discount_id' => $discount->id,
    ])->assertRedirect();

    $studentDiscount = StudentDiscount::query()->first();

    expect($studentDiscount)->not->toBeNull();
    expect($studentDiscount->student_id)->toBe($student->id);
    expect($studentDiscount->discount_id)->toBe($discount->id);
    expect($studentDiscount->academic_year_id)->toBe($academicYear->id);

    $this->delete("/finance/discount-manager/tag-student/{$studentDiscount->id}")
        ->assertRedirect();

    expect(StudentDiscount::query()->whereKey($studentDiscount->id)->exists())->toBeFalse();

    $this->delete("/finance/discount-manager/{$discount->id}")
        ->assertRedirect();

    expect(Discount::query()->whereKey($discount->id)->exists())->toBeFalse();
});
