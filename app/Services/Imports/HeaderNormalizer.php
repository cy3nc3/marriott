<?php

namespace App\Services\Imports;

class HeaderNormalizer
{
    /**
     * @var array<string, string>
     */
    private array $aliases = [
        'lrn' => 'lrn',
        'student_id' => 'lrn',
        'student_number' => 'lrn',
        'learner_reference_number' => 'lrn',
        'learners_reference_number' => 'lrn',
        'school_year' => 'school_year',
        'academic_year' => 'school_year',
        'academic_year_name' => 'school_year',
        'sy' => 'school_year',
        'name' => 'name',
        'student_name' => 'name',
        'learner_name' => 'name',
        'full_name' => 'name',
        'first_name' => 'first_name',
        'firstname' => 'first_name',
        'given_name' => 'first_name',
        'last_name' => 'last_name',
        'lastname' => 'last_name',
        'surname' => 'last_name',
        'grade_level' => 'grade_level',
        'year_level' => 'grade_level',
        'section' => 'section',
        'section_name' => 'section',
        'gender' => 'gender',
        'sex' => 'gender',
        'birthday' => 'birthdate',
        'birthdate' => 'birthdate',
        'date_of_birth' => 'birthdate',
        'address' => 'address',
        'guardian_name' => 'guardian_name',
        'parent_name' => 'guardian_name',
        'contact_number' => 'contact_number',
        'mobile_number' => 'contact_number',
        'cellphone_number' => 'contact_number',
        'school_name' => 'school_name',
        'general_average' => 'general_average',
        'grades' => 'general_average',
        'average' => 'general_average',
        'final_grade' => 'general_average',
        'status' => 'status',
        'record_status' => 'status',
        'failed_subject_count' => 'failed_subject_count',
        'failed_subjects' => 'failed_subject_count',
        'remarks' => 'remarks',
        'notes' => 'remarks',
        'conditional_resolution_notes' => 'conditional_resolution_notes',
        'or_number' => 'or_number',
        'or_no' => 'or_number',
        'receipt_no' => 'or_number',
        'receipt_number' => 'or_number',
        'payment_date' => 'payment_date',
        'date' => 'payment_date',
        'transaction_date' => 'payment_date',
        'posted_at' => 'payment_date',
        'payment_mode' => 'payment_mode',
        'payment_method' => 'payment_mode',
        'method' => 'payment_mode',
        'amount' => 'amount',
        'payment_amount' => 'amount',
        'total_amount' => 'amount',
        'reference_no' => 'reference_no',
        'reference_number' => 'reference_no',
        'reference' => 'reference_no',
        'payment_term' => 'payment_term',
        'payment_plan' => 'payment_term',
        'installment_plan' => 'payment_term',
        'downpayment' => 'downpayment',
        'enrollment_downpayment' => 'downpayment',
        'enrollment_status' => 'enrollment_status',
        'due_date' => 'due_date',
        'billing_due_date' => 'due_date',
        'due_amount' => 'due_amount',
        'amount_due' => 'due_amount',
        'installment_amount' => 'due_amount',
        'due_description' => 'due_description',
        'description' => 'description',
        'entry_description' => 'description',
        'payment_description' => 'description',
        'transaction_description' => 'description',
        'payment_details' => 'description',
        'desc' => 'description',
        'billing_description' => 'description',
        'installment_description' => 'description',
    ];

    public function normalize(string $header): string
    {
        $normalized = strtolower(trim($header));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? '';
        $normalized = preg_replace('/_+/', '_', $normalized) ?? '';

        return trim($normalized, '_');
    }

    public function canonicalize(string $header): string
    {
        $normalized = $this->normalize($header);

        return $this->aliases[$normalized] ?? $normalized;
    }
}
