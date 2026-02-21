<?php

use App\Models\Fee;
use App\Models\GradeLevel;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->finance = User::factory()->finance()->create();
    $this->actingAs($this->finance);
});

test('finance fee structure page renders grouped fee rows', function () {
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
        'type' => 'tuition',
        'name' => 'Tuition Fee',
        'amount' => 20000,
    ]);
    Fee::query()->create([
        'grade_level_id' => $gradeSeven->id,
        'type' => 'miscellaneous',
        'name' => 'Laboratory Fee',
        'amount' => 2500,
    ]);
    Fee::query()->create([
        'grade_level_id' => $gradeEight->id,
        'type' => 'books_modules',
        'name' => 'Books and Modules',
        'amount' => 3000,
    ]);

    $this->get('/finance/fee-structure')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/fee-structure/index')
            ->has('grade_level_fees', 2)
            ->where('grade_level_fees.0.name', 'Grade 7')
            ->where('grade_level_fees.0.fee_items.0.label', 'Laboratory Fee')
            ->where('grade_level_fees.1.name', 'Grade 8')
        );
});

test('finance can create update and delete fee items', function () {
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
        'type' => 'miscellaneous',
        'name' => 'ID and School Paper',
        'amount' => 3000,
    ])->assertRedirect();

    $fee = Fee::query()->first();

    expect($fee)->not->toBeNull();
    expect($fee->name)->toBe('ID and School Paper');
    expect((float) $fee->amount)->toBe(3000.0);

    $this->patch("/finance/fee-structure/{$fee->id}", [
        'grade_level_id' => $gradeEight->id,
        'type' => 'books_modules',
        'name' => 'Books Bundle',
        'amount' => 3500.5,
    ])->assertRedirect();

    $fee->refresh();

    expect($fee->grade_level_id)->toBe($gradeEight->id);
    expect($fee->type)->toBe('books_modules');
    expect($fee->name)->toBe('Books Bundle');
    expect((float) $fee->amount)->toBe(3500.5);

    $this->delete("/finance/fee-structure/{$fee->id}")
        ->assertRedirect();

    expect(Fee::query()->whereKey($fee->id)->exists())->toBeFalse();
});
