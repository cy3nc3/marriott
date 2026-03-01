<?php

use App\Models\AcademicYear;
use App\Models\Fee;
use App\Models\GradeLevel;
use App\Models\RemedialSubjectFee;
use App\Models\Subject;
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
    $gradeSevenSubject = Subject::query()->create([
        'grade_level_id' => $gradeSeven->id,
        'subject_code' => 'G7-MATH',
        'subject_name' => 'Mathematics',
    ]);
    Subject::query()->create([
        'grade_level_id' => $gradeEight->id,
        'subject_code' => 'G8-SCI',
        'subject_name' => 'Science',
    ]);

    RemedialSubjectFee::query()->create([
        'academic_year_id' => $schoolYearTwo->id,
        'subject_id' => $gradeSevenSubject->id,
        'amount' => 900,
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
            ->has('remedial_subject_fees', 2)
            ->where('remedial_subject_fees.0.name', 'Grade 7')
            ->where('remedial_subject_fees.0.subject_fees.0.amount', 900)
            ->where('remedial_subject_fees.0.subject_fees.0.is_custom', true)
            ->where('remedial_subject_fees.1.name', 'Grade 8')
            ->where('remedial_subject_fees.1.subject_fees.0.amount', 500)
            ->where('remedial_subject_fees.1.subject_fees.0.is_custom', false)
        );
});

test('finance fee structure is locked to active school year', function () {
    $completedYear = AcademicYear::query()->create([
        'name' => '2024-2025',
        'start_date' => '2024-06-01',
        'end_date' => '2025-03-31',
        'status' => 'completed',
        'current_quarter' => '4',
    ]);
    $activeYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);

    Fee::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'academic_year_id' => $completedYear->id,
        'type' => 'miscellaneous',
        'name' => 'Completed Year Fee',
        'amount' => 1000,
    ]);
    Fee::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'academic_year_id' => $activeYear->id,
        'type' => 'miscellaneous',
        'name' => 'Active Year Fee',
        'amount' => 1500,
    ]);

    $this->get("/finance/fee-structure?academic_year_id={$completedYear->id}")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/fee-structure/index')
            ->where('selected_school_year_id', $activeYear->id)
            ->where('grade_level_fees.0.fee_items.0.label', 'Active Year Fee')
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

test('finance can update remedial subject fee in fee structure', function () {
    $schoolYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);
    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);
    $subject = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'G7-ENG',
        'subject_name' => 'English',
    ]);

    $this->patch('/finance/fee-structure/remedial-subject-fee', [
        'academic_year_id' => $schoolYear->id,
        'subject_id' => $subject->id,
        'amount' => 750,
    ])->assertRedirect()
        ->assertSessionHas('success', 'Remedial subject fee updated.');

    $record = RemedialSubjectFee::query()
        ->where('academic_year_id', $schoolYear->id)
        ->where('subject_id', $subject->id)
        ->first();

    expect($record)->not->toBeNull();
    expect((float) $record?->amount)->toBe(750.0);
});

test('finance remedial subject fee update validates non negative amount', function () {
    $schoolYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);
    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);
    $subject = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'G7-MAPEH',
        'subject_name' => 'MAPEH',
    ]);

    $this->from('/finance/fee-structure')
        ->patch('/finance/fee-structure/remedial-subject-fee', [
            'academic_year_id' => $schoolYear->id,
            'subject_id' => $subject->id,
            'amount' => -1,
        ])->assertRedirect('/finance/fee-structure')
        ->assertSessionHasErrors(['amount']);
});
