<?php

use App\Models\AccountClaimToken;
use App\Models\User;
use App\Notifications\EnrollmentAccountClaimNotification;
use App\Services\Auth\EnrollmentAccountClaimService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('claim account page can be rendered with a valid token', function () {
    $user = User::factory()->create();
    $plainToken = 'claim-token-demo-123';

    AccountClaimToken::query()->create([
        'user_id' => $user->id,
        'enrollment_id' => null,
        'email' => $user->email,
        'token_hash' => hash('sha256', $plainToken),
        'expires_at' => now()->addDay(),
        'used_at' => null,
    ]);

    $this->get("/account/claim/{$plainToken}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/claim-account')
            ->where('token', $plainToken)
            ->where('phone_verified', false)
        );
});

test('claim token can set password once', function () {
    $user = User::factory()->create([
        'must_change_password' => true,
    ]);
    $plainToken = 'claim-once-token-456';

    $accountClaimToken = AccountClaimToken::query()->create([
        'user_id' => $user->id,
        'enrollment_id' => null,
        'email' => $user->email,
        'token_hash' => hash('sha256', $plainToken),
        'expires_at' => now()->addDay(),
        'used_at' => null,
    ]);

    $this->withSession([
        'account_claim_phone_verified_'.$accountClaimToken->id => true,
    ])->post("/account/claim/{$plainToken}", [
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertRedirect(route('login'));

    $user->refresh();
    $accountClaimToken->refresh();

    expect(Hash::check('new-password-123', $user->password))->toBeTrue();
    expect($user->must_change_password)->toBeFalse();
    expect($accountClaimToken->used_at)->not->toBeNull();
});

test('used claim token cannot be reused', function () {
    $user = User::factory()->create();
    $plainToken = 'claim-reuse-token-789';

    AccountClaimToken::query()->create([
        'user_id' => $user->id,
        'enrollment_id' => null,
        'email' => $user->email,
        'token_hash' => hash('sha256', $plainToken),
        'expires_at' => now()->addDay(),
        'used_at' => now()->subMinute(),
    ]);

    $this->from('/login')
        ->post("/account/claim/{$plainToken}", [
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
        ->assertRedirect('/login')
        ->assertSessionHasErrors('token');
});

test('claim otp can be requested for matching enrollment phone', function () {
    $user = User::factory()->create();
    $academicYear = \App\Models\AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);
    $gradeLevel = \App\Models\GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);
    $student = \App\Models\Student::query()->create([
        'user_id' => $user->id,
        'lrn' => '123451234512',
        'first_name' => 'Otp',
        'last_name' => 'Student',
        'contact_number' => '09171234567',
    ]);

    $enrollment = \App\Models\Enrollment::query()->create([
        'student_id' => $student->id,
        'email' => $user->email,
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'payment_term' => 'monthly',
        'downpayment' => 1000,
        'status' => 'enrolled',
    ]);

    $plainToken = 'claim-otp-token-123';
    AccountClaimToken::query()->create([
        'user_id' => $user->id,
        'enrollment_id' => $enrollment->id,
        'email' => $user->email,
        'token_hash' => hash('sha256', $plainToken),
        'expires_at' => now()->addDay(),
        'used_at' => null,
    ]);

    $this->postJson("/account/claim/{$plainToken}/otp/send", [
        'phone_number' => '09171234567',
    ])->assertOk();
});

test('expired claim token can be refreshed after successful otp verification', function () {
    Http::fake([
        'https://identitytoolkit.googleapis.com/*' => Http::response([
            'users' => [
                [
                    'phoneNumber' => '+639179876543',
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create([
        'must_change_password' => true,
    ]);
    $academicYear = \App\Models\AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);
    $gradeLevel = \App\Models\GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);
    $student = \App\Models\Student::query()->create([
        'user_id' => $user->id,
        'lrn' => '123451234513',
        'first_name' => 'Expired',
        'last_name' => 'Otp',
        'contact_number' => '09179876543',
    ]);

    $enrollment = \App\Models\Enrollment::query()->create([
        'student_id' => $student->id,
        'email' => $user->email,
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'payment_term' => 'monthly',
        'downpayment' => 1000,
        'status' => 'enrolled',
    ]);

    $plainToken = 'expired-claim-token-999';
    $expiredToken = AccountClaimToken::query()->create([
        'user_id' => $user->id,
        'enrollment_id' => $enrollment->id,
        'email' => $user->email,
        'token_hash' => hash('sha256', $plainToken),
        'expires_at' => now()->subMinute(),
        'used_at' => null,
    ]);

    $response = $this->post("/account/claim/{$plainToken}/otp/verify", [
        'id_token' => 'firebase-id-token-demo',
    ]);

    $response->assertRedirect();
    expect((string) $response->headers->get('location'))->toContain('/account/claim/');
    expect(AccountClaimToken::query()->usable()->where('user_id', $user->id)->count())->toBe(1);
});

test('claim notification link uses configured claim base url', function () {
    Notification::fake();

    config()->set('services.enrollment_claim_mail.enabled', true);
    config()->set('services.enrollment_claim_mail.claim_base_url', 'https://msqc.tech');

    $user = User::factory()->create();
    $academicYear = \App\Models\AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);
    $gradeLevel = \App\Models\GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);
    $student = \App\Models\Student::query()->create([
        'user_id' => $user->id,
        'lrn' => '123451234514',
        'first_name' => 'Claim',
        'last_name' => 'Url',
        'contact_number' => '09171230000',
    ]);

    $enrollment = \App\Models\Enrollment::query()->create([
        'student_id' => $student->id,
        'email' => $user->email,
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'payment_term' => 'monthly',
        'downpayment' => 1000,
        'status' => 'enrolled',
    ]);

    app(EnrollmentAccountClaimService::class)->issueForEnrollment($enrollment);

    Notification::assertSentOnDemand(EnrollmentAccountClaimNotification::class, function (EnrollmentAccountClaimNotification $notification): bool {
        $payload = $notification->toArray(new stdClass);

        return str_starts_with((string) $payload['claim_url'], 'https://msqc.tech/account/claim/');
    });
});
