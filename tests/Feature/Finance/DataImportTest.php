<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\LedgerEntry;
use App\Models\Section;
use App\Models\Student;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->finance = User::factory()->finance()->create();
    $this->actingAs($this->finance);
});

test('finance data import page renders', function () {
    $this->get('/finance/data-import')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/data-import/index')
            ->has('imports')
        );
});

test('finance can import historical transaction records from csv', function () {
    $csvContent = implode("\n", [
        'School Year,LRN,Name,Grade Level,Section,OR Number,Payment Date,Payment Method,Amount,Reference No,Remarks,Description',
        '2023-2024,800000000001,"Santos, Leo",Grade 9,Ruby,OR-IMP-9001,2024-03-14,cash,1750.50,REF-001,Historical payment import,Tuition Payment',
    ]);

    $file = UploadedFile::fake()->createWithContent('finance-records.csv', $csvContent);

    $this->post('/finance/data-import/transactions', [
        'import_file' => $file,
    ])->assertRedirect()
        ->assertSessionHas('success');

    $student = Student::query()->where('lrn', '800000000001')->first();
    $academicYear = AcademicYear::query()->where('name', '2023-2024')->first();
    $gradeLevel = GradeLevel::query()->where('name', 'Grade 9')->first();
    $section = Section::query()
        ->where('academic_year_id', $academicYear?->id)
        ->where('grade_level_id', $gradeLevel?->id)
        ->where('name', 'Ruby')
        ->first();

    expect($student)->not->toBeNull();
    expect($academicYear)->not->toBeNull();
    expect($gradeLevel)->not->toBeNull();
    expect($section)->not->toBeNull();
    expect($student?->first_name)->toBe('Leo');
    expect($student?->last_name)->toBe('Santos');

    expect(Enrollment::query()
        ->where('student_id', $student?->id)
        ->where('academic_year_id', $academicYear?->id)
        ->where('grade_level_id', $gradeLevel?->id)
        ->where('section_id', $section?->id)
        ->exists())->toBeTrue();

    $transaction = Transaction::query()->where('or_number', 'OR-IMP-9001')->first();

    expect($transaction)->not->toBeNull();
    expect($transaction?->student_id)->toBe($student?->id);
    expect((float) $transaction?->total_amount)->toBe(1750.5);
    expect($transaction?->payment_mode)->toBe('cash');
    expect($transaction?->reference_no)->toBe('REF-001');
    expect($transaction?->remarks)->toBe('Historical payment import');
    expect($transaction?->created_at?->toDateString())->toBe('2024-03-14');
    expect($transaction?->items()->count())->toBe(1);
    expect($transaction?->items()->first()?->description)->toBe('Tuition Payment');

    expect(LedgerEntry::query()
        ->where('student_id', $student?->id)
        ->where('academic_year_id', $academicYear?->id)
        ->where('reference_id', $transaction?->id)
        ->where('description', 'Imported Payment (OR-IMP-9001)')
        ->where('credit', 1750.5)
        ->exists())->toBeTrue();

    $this->get('/finance/data-import')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/data-import/index')
            ->has('imports', 1)
            ->where('imports.0.file_name', 'finance-records.csv')
            ->where('imports.0.created_transactions', 1)
            ->where('imports.0.imported_rows', 1)
            ->where('imports.0.skipped_rows', 0)
        );
});
