<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Services\Finance\BillingScheduleService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProductionEnrollmentStageSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ProductionBaselineSeeder::class);

        $academicYear = AcademicYear::query()->where('name', '2025-2026')->firstOrFail();
        $academicYear->update([
            'status' => 'upcoming',
            'current_quarter' => '1',
        ]);

        $this->seedCurrentYearEnrollments($academicYear);
    }

    private function seedCurrentYearEnrollments(AcademicYear $academicYear): void
    {
        $sections = Section::query()
            ->where('academic_year_id', $academicYear->id)
            ->orderBy('grade_level_id')
            ->orderBy('name')
            ->get();

        $paymentTerms = ['cash', 'monthly', 'quarterly', 'semi-annual'];
        $billingScheduleService = app(BillingScheduleService::class);

        for ($i = 1; $i <= 45; $i++) {
            $student = $this->upsertStudentWithParent($i);
            $section = $sections[($i - 1) % $sections->count()];
            $paymentTerm = $paymentTerms[($i - 1) % count($paymentTerms)];

            $enrollment = Enrollment::query()->updateOrCreate(
                [
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
                ],
                [
                    'grade_level_id' => $section->grade_level_id,
                    'section_id' => $section->id,
                    'payment_term' => $paymentTerm,
                    'downpayment' => $paymentTerm === 'cash' ? 0 : 1500 + ($i * 25),
                    'status' => $i <= 10 ? 'for_cashier_payment' : 'enrolled',
                ]
            );

            $billingScheduleService->syncForEnrollment($enrollment);
        }
    }

    private function upsertStudentWithParent(int $index): Student
    {
        $lrn = '100000'.str_pad((string) $index, 4, '0', STR_PAD_LEFT);

        $studentUser = User::query()->updateOrCreate(
            ['email' => "student.{$lrn}@marriott.edu"],
            [
                'first_name' => 'Student',
                'last_name' => (string) $index,
                'name' => "Student {$index}",
                'password' => Hash::make('password'),
                'birthday' => '2010-01-01',
                'role' => UserRole::STUDENT,
                'is_active' => true,
            ]
        );

        $parentUser = User::query()->updateOrCreate(
            ['email' => "parent.{$lrn}@marriott.edu"],
            [
                'first_name' => 'Parent',
                'last_name' => (string) $index,
                'name' => "Parent {$index}",
                'password' => Hash::make('password'),
                'birthday' => '1980-01-01',
                'role' => UserRole::PARENT,
                'is_active' => true,
            ]
        );

        $student = Student::query()->updateOrCreate(
            ['lrn' => $lrn],
            [
                'user_id' => $studentUser->id,
                'first_name' => 'Student',
                'last_name' => (string) $index,
                'gender' => $index % 2 === 0 ? 'Male' : 'Female',
                'birthdate' => '2010-01-01',
                'guardian_name' => "Parent {$index}",
                'contact_number' => '0917000'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                'address' => "Demo Address {$index}",
                'is_lis_synced' => $index <= 40,
                'sync_error_flag' => false,
                'sync_error_notes' => null,
            ]
        );

        DB::table('parent_student')->updateOrInsert(
            [
                'parent_id' => $parentUser->id,
                'student_id' => $student->id,
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return $student;
    }
}
