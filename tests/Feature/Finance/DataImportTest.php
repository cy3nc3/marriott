<?php

use App\Models\AcademicYear;
use App\Models\BillingSchedule;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\LedgerEntry;
use App\Models\Section;
use App\Models\Student;
use App\Models\Transaction;
use App\Models\TransactionDueAllocation;
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

test('finance data import rejects csv files and requires xls/xlsx template workbooks', function () {
    $csvContent = implode("\n", [
        'School Year,LRN,Name,Grade Level,Section,OR Number,Payment Date,Payment Method,Amount,Reference No,Remarks,Description',
        '2023-2024,800000000001,"Santos, Leo",Grade 9,Ruby,OR-IMP-9001,2024-03-14,cash,1750.50,REF-001,Historical payment import,Tuition Payment',
    ]);

    $file = UploadedFile::fake()->createWithContent('finance-records.csv', $csvContent);

    $this->post('/finance/data-import/transactions', [
        'import_file' => $file,
    ])->assertRedirect()
        ->assertSessionHasErrors('import_file');
});

test('finance data import rejects csv dues payloads and enforces workbook template upload', function () {
    $csvContent = implode("\n", [
        'School Year,LRN,Name,Grade Level,Section,OR Number,Payment Date,Payment Method,Amount,Reference No,Remarks,Description,Payment Term,Downpayment,Enrollment Status,Due Date,Due Amount,Due Description',
        '2024-2025,900000000001,"Reyes, Pia",Grade 8,Emerald,OR-IMP-9101,2025-07-10,gcash,1200,GC-9101,Monthly installment import,Tuition Installment,monthly,3000,for_cashier_payment,2025-08-01,3000,August Installment',
    ]);

    $file = UploadedFile::fake()->createWithContent('finance-with-dues.csv', $csvContent);

    $this->post('/finance/data-import/transactions', [
        'import_file' => $file,
    ])->assertRedirect()
        ->assertSessionHasErrors('import_file');
});
