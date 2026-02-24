<?php

use App\Models\AcademicYear;
use App\Models\Fee;
use App\Models\GradeLevel;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->finance = User::factory()->finance()->create();
    $this->actingAs($this->finance);
});

test('finance fee structure page renders grouped fee rows', function () {
    $schoolYearOne = AcademicYear::query()->create([
        'name' => '2024-2025',
        'start_date' => '2024-06-01',
        'end_date' => '2025-03-31',
        'status' => 'completed',
        'current_quarter' => '4',
    ]);
    $schoolYearTwo = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $gradeSeven = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);
    $gradeEight = GradeLevel::query()->create([
        'name' => 'Grade 8',
        'level_order' => 8,
    ]);

    Fee::query()->create([
        'grade_level_id' => $gradeSeven->id,
        'academic_year_id' => $schoolYearOne->id,
        'type' => 'tuition',
        'name' => 'Tuition Fee',
        'amount' => 20000,
    ]);
    Fee::query()->create([
        'grade_level_id' => $gradeSeven->id,
        'academic_year_id' => $schoolYearTwo->id,
        'type' => 'miscellaneous',
        'name' => 'Laboratory Fee',
        'amount' => 2500,
    ]);
    Fee::query()->create([
        'grade_level_id' => $gradeEight->id,
        'academic_year_id' => $schoolYearTwo->id,
        'type' => 'books_modules',
        'name' => 'Books and Modules',
        'amount' => 3000,
    ]);

    $this->get("/finance/fee-structure?academic_year_id={$schoolYearTwo->id}")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/fee-structure/index')
            ->where('selected_school_year_id', $schoolYearTwo->id)
            ->has('grade_level_fees', 2)
            ->where('grade_level_fees.0.name', 'Grade 7')
            ->where('grade_level_fees.0.fee_items.0.label', 'Laboratory Fee')
            ->where('grade_level_fees.1.name', 'Grade 8')
        );
});

test('finance can create update and delete fee items', function () {
    $schoolYearOne = AcademicYear::query()->create([
        'name' => '2024-2025',
        'start_date' => '2024-06-01',
        'end_date' => '2025-03-31',
        'status' => 'completed',
        'current_quarter' => '4',
    ]);
    $schoolYearTwo = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $gradeSeven = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);
    $gradeEight = GradeLevel::query()->create([
        'name' => 'Grade 8',
        'level_order' => 8,
    ]);

    $this->post('/finance/fee-structure', [
        'grade_level_id' => $gradeSeven->id,
        'academic_year_id' => $schoolYearOne->id,
        'type' => 'miscellaneous',
        'name' => 'ID and School Paper',
        'amount' => 3000,
    ])->assertRedirect();

    $fee = Fee::query()->first();

    expect($fee)->not->toBeNull();
    expect($fee->name)->toBe('ID and School Paper');
    expect($fee->academic_year_id)->toBe($schoolYearOne->id);
    expect((float) $fee->amount)->toBe(3000.0);

    $this->patch("/finance/fee-structure/{$fee->id}", [
        'grade_level_id' => $gradeEight->id,
        'academic_year_id' => $schoolYearTwo->id,
        'type' => 'books_modules',
        'name' => 'Books Bundle',
        'amount' => 3500.5,
    ])->assertRedirect();

    $fee->refresh();

    expect($fee->grade_level_id)->toBe($gradeEight->id);
    expect($fee->academic_year_id)->toBe($schoolYearTwo->id);
    expect($fee->type)->toBe('books_modules');
    expect($fee->name)->toBe('Books Bundle');
    expect((float) $fee->amount)->toBe(3500.5);

    $this->delete("/finance/fee-structure/{$fee->id}")
        ->assertRedirect();

    expect(Fee::query()->whereKey($fee->id)->exists())->toBeFalse();
});
