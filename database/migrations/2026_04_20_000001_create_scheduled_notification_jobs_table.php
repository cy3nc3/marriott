<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $types = [
        'finance_due_reminder',
        'grade_deadline_reminder',
        'announcement_event_reminder',
    ];

    /**
     * @var array<int, string>
     */
    private array $statuses = [
        'pending',
        'canceled',
        'superseded',
        'dispatched',
        'skipped',
        'failed',
    ];

    public function up(): void
    {
        Schema::create('scheduled_notification_jobs', function (Blueprint $table) {
            $table->id();
            $table->enum('type', $this->types);
            $table->enum('status', $this->statuses);
            $table->timestamp('run_at');
            $table->string('dedupe_key')->unique();
            $table->string('group_key');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('recipient_type')->nullable();
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->json('payload')->nullable();
            $table->string('planned_by_type')->nullable();
            $table->unsignedBigInteger('planned_by_id')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->text('skip_reason')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'run_at']);
            $table->index(['type', 'status']);
            $table->index('group_key');
            $table->index(['subject_type', 'subject_id']);
            $table->index(['recipient_type', 'recipient_id']);
        });

        $this->addNullablePairConstraints();
    }

    public function down(): void
    {
        $this->dropNullablePairConstraints();

        Schema::dropIfExists('scheduled_notification_jobs');
    }

    private function addNullablePairConstraints(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER scheduled_notification_jobs_validate_insert
                BEFORE INSERT ON scheduled_notification_jobs
                FOR EACH ROW
                WHEN (
                    (NEW.recipient_type IS NULL AND NEW.recipient_id IS NOT NULL)
                    OR (NEW.recipient_type IS NOT NULL AND NEW.recipient_id IS NULL)
                    OR (NEW.planned_by_type IS NULL AND NEW.planned_by_id IS NOT NULL)
                    OR (NEW.planned_by_type IS NOT NULL AND NEW.planned_by_id IS NULL)
                )
                BEGIN
                    SELECT RAISE(FAIL, 'scheduled_notification_jobs_nullable_pairs_check');
                END;
            SQL);

            DB::unprepared(<<<'SQL'
                CREATE TRIGGER scheduled_notification_jobs_validate_update
                BEFORE UPDATE ON scheduled_notification_jobs
                FOR EACH ROW
                WHEN (
                    (NEW.recipient_type IS NULL AND NEW.recipient_id IS NOT NULL)
                    OR (NEW.recipient_type IS NOT NULL AND NEW.recipient_id IS NULL)
                    OR (NEW.planned_by_type IS NULL AND NEW.planned_by_id IS NOT NULL)
                    OR (NEW.planned_by_type IS NOT NULL AND NEW.planned_by_id IS NULL)
                )
                BEGIN
                    SELECT RAISE(FAIL, 'scheduled_notification_jobs_nullable_pairs_check');
                END;
            SQL);

            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE scheduled_notification_jobs
            ADD CONSTRAINT scheduled_notification_jobs_recipient_pair_check
            CHECK (
                (recipient_type IS NULL AND recipient_id IS NULL)
                OR (recipient_type IS NOT NULL AND recipient_id IS NOT NULL)
            )
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE scheduled_notification_jobs
            ADD CONSTRAINT scheduled_notification_jobs_planned_by_pair_check
            CHECK (
                (planned_by_type IS NULL AND planned_by_id IS NULL)
                OR (planned_by_type IS NOT NULL AND planned_by_id IS NOT NULL)
            )
        SQL);
    }

    private function dropNullablePairConstraints(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP TRIGGER IF EXISTS scheduled_notification_jobs_validate_insert');
            DB::statement('DROP TRIGGER IF EXISTS scheduled_notification_jobs_validate_update');

            return;
        }

        DB::statement('ALTER TABLE scheduled_notification_jobs DROP CONSTRAINT IF EXISTS scheduled_notification_jobs_recipient_pair_check');
        DB::statement('ALTER TABLE scheduled_notification_jobs DROP CONSTRAINT IF EXISTS scheduled_notification_jobs_planned_by_pair_check');
    }
};
