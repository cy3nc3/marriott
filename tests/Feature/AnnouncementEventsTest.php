<?php

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\AnnouncementEventResponse;
use App\Models\AnnouncementRead;
use App\Models\AnnouncementRecipient;
use App\Models\AnnouncementReminderDispatch;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\TeacherSubject;
use App\Models\User;

it('enforces teacher event scope when selecting target users', function (): void {
    $teacher = User::factory()->teacher()->create();
    $parent = User::factory()->parent()->create();
    $outsideUser = User::factory()->student()->create();
    $studentUser = User::factory()->student()->create();

    $academicYear = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);

    $subject = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'MATH7',
        'subject_name' => 'Mathematics 7',
    ]);

    $section = Section::query()->create([
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Rizal',
        'adviser_id' => $teacher->id,
    ]);

    $teacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
    ]);

    SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    $student = Student::query()->create([
        'user_id' => $studentUser->id,
        'lrn' => '100000000001',
        'first_name' => 'Ana',
        'last_name' => 'Ramos',
    ]);

    $parent->students()->attach($student->id);

    Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'monthly',
        'status' => 'enrolled',
    ]);

    $this->actingAs($teacher)
        ->from('/announcements')
        ->post('/announcements', [
            'title' => 'Teacher Event',
            'content' => 'Please confirm attendance.',
            'type' => 'event',
            'target_user_ids' => [$outsideUser->id],
            'event_starts_at' => now()->addDays(3)->toDateTimeString(),
            'response_deadline_at' => now()->addDays(2)->toDateTimeString(),
        ])
        ->assertRedirect('/announcements')
        ->assertSessionHasErrors('target_user_ids');

    $this->actingAs($teacher)
        ->post('/announcements', [
            'title' => 'Teacher Event',
            'content' => 'Please confirm attendance.',
            'type' => 'event',
            'target_roles' => ['student', 'parent'],
            'target_user_ids' => [$studentUser->id, $parent->id],
            'event_starts_at' => now()->addDays(3)->toDateTimeString(),
            'response_deadline_at' => now()->addDays(2)->toDateTimeString(),
        ])
        ->assertRedirect();

    $announcement = Announcement::query()->latest('id')->firstOrFail();

    expect($announcement->type)->toBe('event');

    $recipientUserIds = AnnouncementRecipient::query()
        ->where('announcement_id', $announcement->id)
        ->pluck('user_id')
        ->map(fn (int|string $userId): int => (int) $userId)
        ->all();

    expect($recipientUserIds)->toEqualCanonicalizing([$studentUser->id, $parent->id]);
});

it('always enforces active school-year scope for announcement recipients', function (): void {
    $teacher = User::factory()->teacher()->create();
    $pastStudentUser = User::factory()->student()->create();
    $currentStudentUser = User::factory()->student()->create();

    $pastAcademicYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'completed',
        'current_quarter' => '4',
    ]);

    $currentAcademicYear = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);

    $pastSection = Section::query()->create([
        'academic_year_id' => $pastAcademicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Past-Rizal',
        'adviser_id' => $teacher->id,
    ]);

    $currentSection = Section::query()->create([
        'academic_year_id' => $currentAcademicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Current-Rizal',
        'adviser_id' => $teacher->id,
    ]);

    $pastStudent = Student::query()->create([
        'user_id' => $pastStudentUser->id,
        'lrn' => '100000000101',
        'first_name' => 'Past',
        'last_name' => 'Student',
    ]);

    $currentStudent = Student::query()->create([
        'user_id' => $currentStudentUser->id,
        'lrn' => '100000000102',
        'first_name' => 'Current',
        'last_name' => 'Student',
    ]);

    Enrollment::query()->create([
        'student_id' => $pastStudent->id,
        'academic_year_id' => $pastAcademicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $pastSection->id,
        'payment_term' => 'monthly',
        'status' => 'enrolled',
    ]);

    Enrollment::query()->create([
        'student_id' => $currentStudent->id,
        'academic_year_id' => $currentAcademicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $currentSection->id,
        'payment_term' => 'monthly',
        'status' => 'enrolled',
    ]);

    $this->actingAs($teacher)
        ->from('/announcements')
        ->post('/announcements', [
            'title' => 'Year Scope Enforcement',
            'content' => 'Past-year students should not be targetable.',
            'type' => 'event',
            'audience_academic_year_id' => $pastAcademicYear->id,
            'target_roles' => ['student'],
            'target_user_ids' => [$pastStudentUser->id],
            'event_starts_at' => now()->addDays(3)->toDateTimeString(),
            'response_deadline_at' => now()->addDays(2)->toDateTimeString(),
        ])
        ->assertRedirect('/announcements')
        ->assertSessionHasErrors('target_user_ids');

    $this->assertDatabaseMissing('announcements', [
        'title' => 'Year Scope Enforcement',
    ]);
});

it('allows recipient acknowledgement and rsvp updates idempotently', function (): void {
    $publisher = User::factory()->admin()->create();
    $recipient = User::factory()->teacher()->create();

    $announcement = Announcement::query()->create([
        'user_id' => $publisher->id,
        'title' => 'Faculty Meeting',
        'content' => 'Please RSVP for the faculty meeting.',
        'type' => 'event',
        'response_mode' => 'ack_rsvp',
        'target_roles' => ['teacher'],
        'is_active' => true,
        'event_starts_at' => now()->addDays(2),
        'response_deadline_at' => now()->addDay(),
        'expires_at' => now()->addDays(5),
    ]);

    AnnouncementRecipient::query()->create([
        'announcement_id' => $announcement->id,
        'user_id' => $recipient->id,
        'role' => UserRole::TEACHER->value,
    ]);

    $this->actingAs($recipient)
        ->post("/notifications/announcements/{$announcement->id}/acknowledge")
        ->assertRedirect();

    $this->actingAs($recipient)
        ->post("/notifications/announcements/{$announcement->id}/respond", [
            'response' => 'yes',
            'note' => 'I will attend.',
        ])
        ->assertRedirect();

    expect(AnnouncementEventResponse::query()
        ->where('announcement_id', $announcement->id)
        ->where('user_id', $recipient->id)
        ->count())->toBe(1);

    $response = AnnouncementEventResponse::query()
        ->where('announcement_id', $announcement->id)
        ->where('user_id', $recipient->id)
        ->firstOrFail();

    expect($response->response)->toBe('yes');
    expect($response->note)->toBe('I will attend.');

    $this->assertDatabaseHas('announcement_reads', [
        'announcement_id' => $announcement->id,
        'user_id' => $recipient->id,
    ]);
});

it('blocks event responses from non-recipients and allows recipient students', function (): void {
    $publisher = User::factory()->admin()->create();
    $recipient = User::factory()->teacher()->create();
    $notRecipient = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();

    $announcement = Announcement::query()->create([
        'user_id' => $publisher->id,
        'title' => 'Teacher Briefing',
        'content' => 'Please acknowledge this event.',
        'type' => 'event',
        'response_mode' => 'ack_rsvp',
        'target_roles' => ['teacher', 'student'],
        'is_active' => true,
        'event_starts_at' => now()->addDays(2),
        'response_deadline_at' => now()->addDay(),
        'expires_at' => now()->addDays(4),
    ]);

    AnnouncementRecipient::query()->insert([
        [
            'announcement_id' => $announcement->id,
            'user_id' => $recipient->id,
            'role' => UserRole::TEACHER->value,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'announcement_id' => $announcement->id,
            'user_id' => $student->id,
            'role' => UserRole::STUDENT->value,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $this->actingAs($notRecipient)
        ->post("/notifications/announcements/{$announcement->id}/respond", [
            'response' => 'yes',
        ])
        ->assertForbidden();

    $this->actingAs($student)
        ->post("/notifications/announcements/{$announcement->id}/respond", [
            'response' => 'yes',
        ])
        ->assertRedirect();

    $this->assertDatabaseMissing('announcement_event_responses', [
        'announcement_id' => $announcement->id,
        'user_id' => $notRecipient->id,
    ]);

    $this->assertDatabaseHas('announcement_event_responses', [
        'announcement_id' => $announcement->id,
        'user_id' => $student->id,
        'response' => 'yes',
    ]);
});

it('event reminder command dispatches once per recipient and phase', function (): void {
    $publisher = User::factory()->admin()->create();
    $recipient = User::factory()->parent()->create();

    $announcement = Announcement::query()->create([
        'user_id' => $publisher->id,
        'title' => 'Parent Orientation',
        'content' => 'Please respond to this event notice.',
        'type' => 'event',
        'response_mode' => 'ack_rsvp',
        'target_roles' => ['parent'],
        'is_active' => true,
        'event_starts_at' => now()->addDay(),
        'response_deadline_at' => now()->addDay(),
        'expires_at' => now()->addDays(5),
    ]);

    AnnouncementRecipient::query()->create([
        'announcement_id' => $announcement->id,
        'user_id' => $recipient->id,
        'role' => UserRole::PARENT->value,
    ]);

    AnnouncementRead::query()->create([
        'announcement_id' => $announcement->id,
        'user_id' => $recipient->id,
        'read_at' => now(),
    ]);

    $referenceDate = now()->toDateString();

    $this->artisan('announcements:send-event-reminders', [
        '--date' => $referenceDate,
    ])->assertSuccessful();

    expect(AnnouncementReminderDispatch::query()->count())->toBe(1);

    $this->assertDatabaseMissing('announcement_reads', [
        'announcement_id' => $announcement->id,
        'user_id' => $recipient->id,
    ]);

    $this->artisan('announcements:send-event-reminders', [
        '--date' => $referenceDate,
    ])->assertSuccessful();

    expect(AnnouncementReminderDispatch::query()->count())->toBe(1);
});
