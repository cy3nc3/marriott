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
    ])->assertRedirect(route('account.claim.show', ['token' => $plainToken]))
        ->assertSessionHas('claim_completed', true);

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
    $parentUser = User::factory()->parent()->create();
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
    $parentUser->students()->attach($student->id);

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
    $parentUser = User::factory()->parent()->create();
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
    $parentUser->students()->attach($student->id);

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

        return str_starts_with((string) $payload['student_claim_url'], 'https://msqc.tech/account/claim/')
            && ($payload['parent_claim_url'] === null
                || str_starts_with((string) $payload['parent_claim_url'], 'https://msqc.tech/account/claim/'));
    });
});

test('claim tokens are issued for both student and parent accounts when enrollment is enrolled', function () {
    Notification::fake();

    config()->set('services.enrollment_claim_mail.enabled', true);

    $studentUser = User::factory()->student()->create([
        'email' => 'student.claim@example.com',
    ]);
    $parentUser = User::factory()->parent()->create([
        'email' => 'parent.claim@example.com',
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
        'user_id' => $studentUser->id,
        'lrn' => '123451234515',
        'first_name' => 'Dual',
        'last_name' => 'Claim',
        'contact_number' => '09179998888',
    ]);
    $parentUser->students()->attach($student->id);

    $enrollment = \App\Models\Enrollment::query()->create([
        'student_id' => $student->id,
        'email' => 'guardian.receiver@example.com',
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'payment_term' => 'monthly',
        'downpayment' => 1000,
        'status' => 'enrolled',
    ]);

    app(EnrollmentAccountClaimService::class)->issueForEnrollment($enrollment);

    expect(AccountClaimToken::query()->where('enrollment_id', $enrollment->id)->count())->toBe(2);
    expect(AccountClaimToken::query()->where('enrollment_id', $enrollment->id)->where('user_id', $studentUser->id)->exists())->toBeTrue();
    expect(AccountClaimToken::query()->where('enrollment_id', $enrollment->id)->where('user_id', $parentUser->id)->exists())->toBeTrue();

    Notification::assertSentOnDemandTimes(EnrollmentAccountClaimNotification::class, 1);
});

test('expired parent claim token refresh issues replacement for parent account', function () {
    Http::fake([
        'https://identitytoolkit.googleapis.com/*' => Http::response([
            'users' => [
                [
                    'phoneNumber' => '+639179876543',
                ],
            ],
        ], 200),
    ]);

    $studentUser = User::factory()->student()->create();
    $parentUser = User::factory()->parent()->create([
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
        'user_id' => $studentUser->id,
        'lrn' => '123451234516',
        'first_name' => 'Expired',
        'last_name' => 'ParentClaim',
        'contact_number' => '09179876543',
    ]);
    $parentUser->students()->attach($student->id);

    $enrollment = \App\Models\Enrollment::query()->create([
        'student_id' => $student->id,
        'email' => 'guardian.receiver@example.com',
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'payment_term' => 'monthly',
        'downpayment' => 1000,
        'status' => 'enrolled',
    ]);

    $plainToken = 'expired-parent-claim-token-999';
    AccountClaimToken::query()->create([
        'user_id' => $parentUser->id,
        'enrollment_id' => $enrollment->id,
        'email' => $parentUser->email,
        'token_hash' => hash('sha256', $plainToken),
        'expires_at' => now()->subMinute(),
        'used_at' => null,
    ]);

    $response = $this->post("/account/claim/{$plainToken}/otp/verify", [
        'id_token' => 'firebase-id-token-demo',
    ]);

    $response->assertRedirect();
    expect(AccountClaimToken::query()->usable()->where('user_id', $parentUser->id)->count())->toBe(1);
    expect(AccountClaimToken::query()->usable()->where('user_id', $studentUser->id)->count())->toBe(0);
});

test('claim tokens are not issued when parent account is missing', function () {
    Notification::fake();

    config()->set('services.enrollment_claim_mail.enabled', true);

    $studentUser = User::factory()->student()->create([
        'email' => 'student.only@example.com',
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
        'user_id' => $studentUser->id,
        'lrn' => '123451234517',
        'first_name' => 'Student',
        'last_name' => 'Only',
        'contact_number' => '09179998889',
    ]);

    $enrollment = \App\Models\Enrollment::query()->create([
        'student_id' => $student->id,
        'email' => 'guardian.receiver@example.com',
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'payment_term' => 'monthly',
        'downpayment' => 1000,
        'status' => 'enrolled',
    ]);

    $result = app(EnrollmentAccountClaimService::class)->issueForEnrollment($enrollment);

    expect($result)->toBeNull();
    expect(AccountClaimToken::query()->where('enrollment_id', $enrollment->id)->count())->toBe(0);
    Notification::assertNothingSent();
});
