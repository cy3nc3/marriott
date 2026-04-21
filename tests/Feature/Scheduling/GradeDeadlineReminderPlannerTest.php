<?php

use App\Enums\ScheduledNotificationJobStatus;
use App\Enums\ScheduledNotificationJobType;
use App\Jobs\SendGradeDeadlineReminderJob;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\GradeLevel;
use App\Models\GradeSubmission;
use App\Models\ScheduledNotificationJob;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\TeacherSubject;
use App\Models\User;
use App\Services\GradeDeadlineAnnouncementService;
use App\Services\Scheduling\GradeDeadlineReminderPlanner;
use Illuminate\Support\Carbon;

test('grade planner creates tomorrow and day-of pending jobs for configured deadline', function () {
    Carbon::setTestNow('2026-05-01 08:00:00');
    $fixture = createGradeDeadlineReminderFixture();
    Setting::set("grade_submission_deadline_{$fixture['academicYear']->id}_q1", '2026-05-10 17:00:00', 'grading');
    Setting::set('grade_deadline_reminder_auto_send_enabled', true, 'grading');
    Setting::set('grade_deadline_reminder_send_time', '07:00', 'grading');

    app(GradeDeadlineReminderPlanner::class)
        ->reconcileAcademicYearQuarter($fixture['academicYear'], '1');

    $jobs = ScheduledNotificationJob::query()
        ->where('type', ScheduledNotificationJobType::GradeDeadlineReminder)
        ->orderBy('run_at')
        ->get();

    expect($jobs)->toHaveCount(2)
        ->and($jobs->pluck('status')->all())->toBe([
            ScheduledNotificationJobStatus::Pending,
            ScheduledNotificationJobStatus::Pending,
        ])
        ->and($jobs->pluck('run_at')->map->format('Y-m-d H:i')->all())->toBe([
            '2026-05-09 07:00',
            '2026-05-10 07:00',
        ])
        ->and($jobs->pluck('payload')->all())->each->toHaveKeys(['academic_year_id', 'quarter', 'phase', 'deadline']);

    Carbon::setTestNow();
});

test('grade planner supersedes pending jobs when the deadline changes', function () {
    Carbon::setTestNow('2026-05-01 08:00:00');
    $fixture = createGradeDeadlineReminderFixture();
    Setting::set("grade_submission_deadline_{$fixture['academicYear']->id}_q1", '2026-05-10 17:00:00', 'grading');
    Setting::set('grade_deadline_reminder_auto_send_enabled', true, 'grading');
    Setting::set('grade_deadline_reminder_send_time', '07:00', 'grading');

    $planner = app(GradeDeadlineReminderPlanner::class);
    $planner->reconcileAcademicYearQuarter($fixture['academicYear'], '1');

    Setting::set("grade_submission_deadline_{$fixture['academicYear']->id}_q1", '2026-05-12 17:00:00', 'grading');
    $planner->reconcileAcademicYearQuarter($fixture['academicYear'], '1');

    $pendingRunTimes = ScheduledNotificationJob::query()
        ->where('status', ScheduledNotificationJobStatus::Pending)
        ->orderBy('run_at')
        ->get()
        ->pluck('run_at')
        ->map->format('Y-m-d H:i')
        ->all();

    expect(ScheduledNotificationJob::query()->where('status', ScheduledNotificationJobStatus::Superseded)->count())->toBe(2)
        ->and($pendingRunTimes)->toBe([
            '2026-05-11 07:00',
            '2026-05-12 07:00',
        ]);

    Carbon::setTestNow();
});

test('grade planner cancels pending jobs when automation is disabled', function () {
    Carbon::setTestNow('2026-05-01 08:00:00');
    $fixture = createGradeDeadlineReminderFixture();
    Setting::set("grade_submission_deadline_{$fixture['academicYear']->id}_q1", '2026-05-10 17:00:00', 'grading');
    Setting::set('grade_deadline_reminder_auto_send_enabled', true, 'grading');
    Setting::set('grade_deadline_reminder_send_time', '07:00', 'grading');

    $planner = app(GradeDeadlineReminderPlanner::class);
    $planner->reconcileAcademicYearQuarter($fixture['academicYear'], '1');

    Setting::set('grade_deadline_reminder_auto_send_enabled', false, 'grading');
    $planner->reconcileAcademicYearQuarter($fixture['academicYear'], '1');

    expect(ScheduledNotificationJob::query()->where('status', ScheduledNotificationJobStatus::Canceled)->count())->toBe(2)
        ->and(ScheduledNotificationJob::query()->where('status', ScheduledNotificationJobStatus::Pending)->count())->toBe(0)
        ->and(ScheduledNotificationJob::query()->first()?->skip_reason)->toBe('automation_disabled');

    Carbon::setTestNow();
});

test('grade planner cancels pending jobs when all grades are submitted', function () {
    Carbon::setTestNow('2026-05-01 08:00:00');
    $fixture = createGradeDeadlineReminderFixture(status: GradeSubmission::STATUS_DRAFT);
    Setting::set("grade_submission_deadline_{$fixture['academicYear']->id}_q1", '2026-05-10 17:00:00', 'grading');
    Setting::set('grade_deadline_reminder_auto_send_enabled', true, 'grading');
    Setting::set('grade_deadline_reminder_send_time', '07:00', 'grading');

    app(GradeDeadlineReminderPlanner::class)
        ->reconcileAcademicYearQuarter($fixture['academicYear'], '1');

    $fixture['submission']->forceFill([
        'status' => GradeSubmission::STATUS_SUBMITTED,
        'submitted_at' => now(),
    ])->save();

    expect(ScheduledNotificationJob::query()->where('status', ScheduledNotificationJobStatus::Canceled)->count())->toBe(2)
        ->and(ScheduledNotificationJob::query()->first()?->skip_reason)->toBe('all_grades_submitted');

    Carbon::setTestNow();
});

test('grade deadline send job posts a scheduled reminder once', function () {
    Carbon::setTestNow('2026-05-09 07:00:00');
    $fixture = createGradeDeadlineReminderFixture(status: GradeSubmission::STATUS_DRAFT);
    $admin = User::factory()->admin()->create();
    Setting::set("grade_submission_deadline_{$fixture['academicYear']->id}_q1", '2026-05-10 17:00:00', 'grading');
    Setting::set('grade_deadline_reminder_auto_send_enabled', true, 'grading');
    Setting::set('grade_deadline_reminder_send_time', '07:00', 'grading');

    $job = ScheduledNotificationJob::query()->create([
        'type' => ScheduledNotificationJobType::GradeDeadlineReminder,
        'status' => ScheduledNotificationJobStatus::Pending,
        'run_at' => '2026-05-09 07:00:00',
        'dedupe_key' => "grading:ay-{$fixture['academicYear']->id}:q1:tomorrow:202605101700",
        'group_key' => "grading:ay-{$fixture['academicYear']->id}:q1",
        'subject_type' => AcademicYear::class,
        'subject_id' => $fixture['academicYear']->id,
        'payload' => [
            'academic_year_id' => $fixture['academicYear']->id,
            'quarter' => '1',
            'phase' => 'tomorrow',
            'deadline' => '2026-05-10 17:00:00',
        ],
    ]);

    (new SendGradeDeadlineReminderJob($job->id))
        ->handle(app(GradeDeadlineAnnouncementService::class));

    $job->refresh();

    expect(Announcement::query()->count())->toBe(1)
        ->and(Announcement::query()->first()?->target_user_ids)->toBe([$fixture['teacher']->id])
        ->and(Announcement::query()->first()?->user_id)->toBe($admin->id)
        ->and($job->status)->toBe(ScheduledNotificationJobStatus::Dispatched)
        ->and($job->dispatched_at?->toDateTimeString())->toBe('2026-05-09 07:00:00');

    (new SendGradeDeadlineReminderJob($job->id))
        ->handle(app(GradeDeadlineAnnouncementService::class));

    expect(Announcement::query()->count())->toBe(1);

    Carbon::setTestNow();
});

function createGradeDeadlineReminderFixture(string $status = GradeSubmission::STATUS_DRAFT): array
{
    $academicYear = AcademicYear::query()->create([
        'name' => fake()->unique()->numerify('2026-2027 ###'),
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $gradeLevel = GradeLevel::query()->create([
        'name' => fake()->unique()->numerify('Grade 7 ###'),
        'level_order' => 7,
    ]);

    $teacher = User::factory()->teacher()->create();

    $section = Section::query()->create([
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => fake()->unique()->word(),
        'adviser_id' => null,
    ]);

    $subject = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => fake()->unique()->lexify('SUB???'),
        'subject_name' => fake()->words(2, true),
    ]);

    $teacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
    ]);

    $subjectAssignment = SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    $submission = GradeSubmission::query()->create([
        'academic_year_id' => $academicYear->id,
        'subject_assignment_id' => $subjectAssignment->id,
        'quarter' => '1',
        'status' => $status,
        'submitted_by' => $teacher->id,
        'submitted_at' => $status === GradeSubmission::STATUS_DRAFT ? null : now(),
    ]);

    return [
        'academicYear' => $academicYear,
        'teacher' => $teacher,
        'submission' => $submission,
    ];
}
