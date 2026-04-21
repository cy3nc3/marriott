<?php

use App\Enums\ScheduledNotificationJobStatus;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\GradeLevel;
use App\Models\GradeSubmission;
use App\Models\ScheduledNotificationJob;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\TeacherSubject;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Inertia\Testing\AssertableInertia as Assert;

function createGradeSubmissionFixture(
    string $status = GradeSubmission::STATUS_SUBMITTED,
    string $quarter = '1',
    ?AcademicYear $academicYear = null
): array {
    $year = $academicYear
        ?? AcademicYear::query()->create([
            'name' => '2025-2026',
            'start_date' => '2025-06-01',
            'end_date' => '2026-03-31',
            'status' => 'ongoing',
            'current_quarter' => $quarter,
        ]);

    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);

    $teacher = User::factory()->teacher()->create([
        'first_name' => 'Lea',
        'last_name' => 'Teacher',
    ]);

    $section = Section::query()->create([
        'academic_year_id' => $year->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Rizal',
        'adviser_id' => null,
    ]);

    $subject = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'MATH7',
        'subject_name' => 'Mathematics 7',
    ]);

    $teacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
    ]);

    $subjectAssignment = SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    $studentUser = User::factory()->student()->create();

    $student = Student::query()->create([
        'user_id' => $studentUser->id,
        'lrn' => str_pad((string) (910000000000 + Student::query()->count() + 1), 12, '0', STR_PAD_LEFT),
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'gender' => 'Female',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $year->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'enrolled',
    ]);

    FinalGrade::query()->create([
        'enrollment_id' => $enrollment->id,
        'subject_assignment_id' => $subjectAssignment->id,
        'quarter' => $quarter,
        'grade' => 85,
        'is_locked' => $status !== GradeSubmission::STATUS_RETURNED,
    ]);

    $submission = GradeSubmission::query()->create([
        'academic_year_id' => $year->id,
        'subject_assignment_id' => $subjectAssignment->id,
        'quarter' => $quarter,
        'status' => $status,
        'submitted_by' => $teacher->id,
        'submitted_at' => now()->subHour(),
        'returned_by' => $status === GradeSubmission::STATUS_RETURNED ? $teacher->id : null,
        'returned_at' => $status === GradeSubmission::STATUS_RETURNED ? now()->subMinutes(30) : null,
        'return_notes' => $status === GradeSubmission::STATUS_RETURNED ? 'Please adjust grades.' : null,
    ]);

    return [
        'academicYear' => $year,
        'teacher' => $teacher,
        'subjectAssignment' => $subjectAssignment,
        'studentUser' => $studentUser,
        'submission' => $submission,
    ];
}

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('admin can open grade verification page', function () {
    createGradeSubmissionFixture();

    $this->actingAs($this->admin)
        ->get('/admin/grade-verification')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/grade-verification/index')
            ->where('summary.submitted_count', 1)
            ->where('context.current_quarter', '1')
            ->where('submissions.0.status', 'submitted')
        );
});

test('admin can verify submitted grade submission', function () {
    $fixture = createGradeSubmissionFixture();

    $this->actingAs($this->admin)
        ->post("/admin/grade-verification/{$fixture['submission']->id}/verify")
        ->assertRedirect();

    $this->assertDatabaseHas('grade_submissions', [
        'id' => $fixture['submission']->id,
        'status' => GradeSubmission::STATUS_VERIFIED,
        'verified_by' => $this->admin->id,
    ]);

    $this->assertDatabaseHas('final_grades', [
        'subject_assignment_id' => $fixture['subjectAssignment']->id,
        'quarter' => '1',
        'is_locked' => true,
    ]);
});

test('admin can return submitted grade submission and unlock grade rows', function () {
    $fixture = createGradeSubmissionFixture();

    $this->actingAs($this->admin)
        ->post("/admin/grade-verification/{$fixture['submission']->id}/return", [
            'return_notes' => 'Incomplete data. Please review score encoding.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('grade_submissions', [
        'id' => $fixture['submission']->id,
        'status' => GradeSubmission::STATUS_RETURNED,
        'returned_by' => $this->admin->id,
        'return_notes' => 'Incomplete data. Please review score encoding.',
    ]);

    $this->assertDatabaseHas('final_grades', [
        'subject_assignment_id' => $fixture['subjectAssignment']->id,
        'quarter' => '1',
        'is_locked' => false,
    ]);
});

test('setting deadline creates teacher announcement only for pending teachers', function () {
    $pendingFixture = createGradeSubmissionFixture(status: GradeSubmission::STATUS_DRAFT);
    $finalizedFixture = createGradeSubmissionFixture(
        status: GradeSubmission::STATUS_SUBMITTED,
        academicYear: $pendingFixture['academicYear']
    );

    $this->actingAs($this->admin)
        ->post('/admin/grade-verification/deadline', [
            'submission_deadline' => '2026-05-31 17:00:00',
        ])
        ->assertRedirect();

    expect(Setting::get("grade_submission_deadline_{$pendingFixture['academicYear']->id}_q1"))
        ->not
        ->toBeNull();

    $announcement = Announcement::query()->latest('id')->first();

    expect($announcement)->not->toBeNull();
    expect($announcement?->title)->toContain('Deadline Set');
    expect($announcement?->target_roles)->toBe(['teacher']);
    expect($announcement?->target_user_ids)->toContain($pendingFixture['teacher']->id);
    expect($announcement?->target_user_ids)->not->toContain($finalizedFixture['teacher']->id);
    expect(ScheduledNotificationJob::query()->where('status', ScheduledNotificationJobStatus::Pending)->count())->toBe(2);
});

test('editing deadline posts updated announcement', function () {
    createGradeSubmissionFixture(status: GradeSubmission::STATUS_DRAFT);

    $this->actingAs($this->admin)
        ->post('/admin/grade-verification/deadline', [
            'submission_deadline' => '2026-05-30 17:00:00',
        ])
        ->assertRedirect();

    $this->actingAs($this->admin)
        ->post('/admin/grade-verification/deadline', [
            'submission_deadline' => '2026-06-02 17:00:00',
        ])
        ->assertRedirect();

    $latestAnnouncement = Announcement::query()->latest('id')->first();

    expect($latestAnnouncement)->not->toBeNull();
    expect($latestAnnouncement?->title)->toContain('Deadline Updated');
    expect(ScheduledNotificationJob::query()->where('status', ScheduledNotificationJobStatus::Superseded)->count())->toBe(2);
    expect(ScheduledNotificationJob::query()->where('status', ScheduledNotificationJobStatus::Pending)->count())->toBe(2);
});

test('admin can update grade reminder automation settings', function () {
    $fixture = createGradeSubmissionFixture(status: GradeSubmission::STATUS_DRAFT);
    Setting::set(
        "grade_submission_deadline_{$fixture['academicYear']->id}_q1",
        '2026-05-25 17:00:00',
        'grading'
    );
    Setting::set('grade_deadline_reminder_auto_send_enabled', true, 'grading');
    app(\App\Services\Scheduling\GradeDeadlineReminderPlanner::class)
        ->reconcileAcademicYearQuarter($fixture['academicYear'], '1');

    $this->actingAs($this->admin)
        ->patch('/admin/grade-verification/reminder-automation', [
            'auto_send_enabled' => false,
            'send_time' => '09:15',
        ])
        ->assertRedirect();

    expect(Setting::get('grade_deadline_reminder_auto_send_enabled'))->toBe('0');
    expect(Setting::get('grade_deadline_reminder_send_time'))->toBe('09:15');
    expect(ScheduledNotificationJob::query()->where('status', ScheduledNotificationJobStatus::Canceled)->count())->toBe(2);
    expect(ScheduledNotificationJob::query()->first()?->skip_reason)->toBe('automation_disabled');
});

test('deadline reminder command posts tomorrow and today reminders without duplicates', function () {
    $fixture = createGradeSubmissionFixture(status: GradeSubmission::STATUS_DRAFT);

    Setting::set(
        "grade_submission_deadline_{$fixture['academicYear']->id}_q1",
        '2026-02-25 17:00:00',
        'grading'
    );

    Artisan::call('grading:send-deadline-reminders', [
        '--date' => '2026-02-24',
    ]);

    expect(Announcement::query()->count())->toBe(1);
    expect(Announcement::query()->latest('id')->value('title'))
        ->toContain('Tomorrow');

    Artisan::call('grading:send-deadline-reminders', [
        '--date' => '2026-02-24',
    ]);

    expect(Announcement::query()->count())->toBe(1);

    Artisan::call('grading:send-deadline-reminders', [
        '--date' => '2026-02-25',
    ]);

    expect(Announcement::query()->count())->toBe(2);
    expect(Announcement::query()->latest('id')->value('title'))
        ->toContain('Today');
});

test('deadline reminder command skips announcements when no teacher has pending grades', function () {
    $fixture = createGradeSubmissionFixture(status: GradeSubmission::STATUS_SUBMITTED);

    Setting::set(
        "grade_submission_deadline_{$fixture['academicYear']->id}_q1",
        '2026-02-25 17:00:00',
        'grading'
    );

    Artisan::call('grading:send-deadline-reminders', [
        '--date' => '2026-02-24',
    ]);

    expect(Announcement::query()->count())->toBe(0);
});

test('deadline reminder command respects automation toggle and force option', function () {
    $fixture = createGradeSubmissionFixture(status: GradeSubmission::STATUS_DRAFT);

    Setting::set(
        "grade_submission_deadline_{$fixture['academicYear']->id}_q1",
        '2026-02-25 17:00:00',
        'grading'
    );
    Setting::set('grade_deadline_reminder_auto_send_enabled', false, 'grading');

    Carbon::setTestNow('2026-02-24 07:00:00');

    Artisan::call('grading:send-deadline-reminders');

    expect(Announcement::query()->count())->toBe(0);

    Artisan::call('grading:send-deadline-reminders', [
        '--force' => true,
    ]);

    expect(Announcement::query()->count())->toBe(1);

    Carbon::setTestNow();
});

test('deadline reminder command only runs at configured send time', function () {
    $fixture = createGradeSubmissionFixture(status: GradeSubmission::STATUS_DRAFT);

    Setting::set(
        "grade_submission_deadline_{$fixture['academicYear']->id}_q1",
        '2026-02-25 17:00:00',
        'grading'
    );
    Setting::set('grade_deadline_reminder_auto_send_enabled', true, 'grading');
    Setting::set('grade_deadline_reminder_send_time', '09:15', 'grading');

    Carbon::setTestNow('2026-02-24 09:14:00');

    Artisan::call('grading:send-deadline-reminders');

    expect(Announcement::query()->count())->toBe(0);

    Carbon::setTestNow('2026-02-24 09:15:00');

    Artisan::call('grading:send-deadline-reminders');

    expect(Announcement::query()->count())->toBe(1);

    Carbon::setTestNow();
});

test('year close is blocked when grade verifications are pending', function () {
    $fixture = createGradeSubmissionFixture();

    $sourceYear = $fixture['academicYear'];
    $sourceYear->update([
        'current_quarter' => '4',
    ]);

    AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => 'upcoming',
        'current_quarter' => '1',
    ]);

    $this->actingAs($this->admin)
        ->post("/admin/academic-controls/{$sourceYear->id}/advance-quarter")
        ->assertSessionHas('error', 'Cannot close school year. Resolve all grade verifications first.');

    expect($sourceYear->fresh()->status)->toBe('ongoing');
});

test('student grade view hides unverified class-quarter grades', function () {
    $fixture = createGradeSubmissionFixture();

    $this->actingAs($fixture['studentUser'])
        ->get('/student/grades')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('subject_rows', [])
        );

    $fixture['submission']->update([
        'status' => GradeSubmission::STATUS_VERIFIED,
        'verified_by' => $this->admin->id,
        'verified_at' => now(),
    ]);

    $this->actingAs($fixture['studentUser'])
        ->get('/student/grades')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('subject_rows', 1)
            ->where('subject_rows.0.subject', 'Mathematics 7')
            ->where('subject_rows.0.q1', '85.00')
        );
});
