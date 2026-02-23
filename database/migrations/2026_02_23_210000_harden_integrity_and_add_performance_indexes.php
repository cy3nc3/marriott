<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->deduplicateEnrollments();
        $this->deduplicateStudentScores();

        Schema::table('enrollments', function (Blueprint $table) {
            $table->unique(['student_id', 'academic_year_id'], 'enrollments_student_year_unique');
            $table->index(['academic_year_id', 'status'], 'enrollments_year_status_index');
            $table->index(['section_id', 'academic_year_id', 'status'], 'enrollments_section_year_status_index');
        });

        Schema::table('student_scores', function (Blueprint $table) {
            $table->unique(['student_id', 'graded_activity_id'], 'student_scores_student_activity_unique');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['student_id', 'created_at'], 'transactions_student_created_index');
            $table->index(['payment_mode', 'created_at'], 'transactions_payment_mode_created_index');
        });

        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->index(['student_id', 'academic_year_id', 'date'], 'ledger_entries_student_year_date_index');
        });

        Schema::table('billing_schedules', function (Blueprint $table) {
            $table->index(['student_id', 'academic_year_id', 'status', 'due_date'], 'billing_schedules_student_year_status_due_index');
        });

        Schema::table('final_grades', function (Blueprint $table) {
            $table->index(['subject_assignment_id', 'quarter', 'is_locked'], 'final_grades_assignment_quarter_locked_index');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['created_at'], 'audit_logs_created_at_index');
            $table->index(['action'], 'audit_logs_action_index');
            $table->index(['model_type', 'model_id'], 'audit_logs_model_index');
        });

        Schema::table('class_schedules', function (Blueprint $table) {
            $table->index(['section_id', 'day', 'start_time', 'end_time'], 'class_schedules_section_day_time_index');
        });
    }

    public function down(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropIndex('class_schedules_section_day_time_index');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_model_index');
            $table->dropIndex('audit_logs_action_index');
            $table->dropIndex('audit_logs_created_at_index');
        });

        Schema::table('final_grades', function (Blueprint $table) {
            $table->dropIndex('final_grades_assignment_quarter_locked_index');
        });

        Schema::table('billing_schedules', function (Blueprint $table) {
            $table->dropIndex('billing_schedules_student_year_status_due_index');
        });

        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropIndex('ledger_entries_student_year_date_index');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_payment_mode_created_index');
            $table->dropIndex('transactions_student_created_index');
        });

        Schema::table('student_scores', function (Blueprint $table) {
            $table->dropUnique('student_scores_student_activity_unique');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex('enrollments_section_year_status_index');
            $table->dropIndex('enrollments_year_status_index');
            $table->dropUnique('enrollments_student_year_unique');
        });
    }

    private function deduplicateEnrollments(): void
    {
        $duplicateGroups = DB::table('enrollments')
            ->select('student_id', 'academic_year_id', DB::raw('count(*) as duplicate_count'))
            ->groupBy('student_id', 'academic_year_id')
            ->havingRaw('count(*) > 1')
            ->get();

        foreach ($duplicateGroups as $duplicateGroup) {
            $rows = DB::table('enrollments')
                ->where('student_id', $duplicateGroup->student_id)
                ->where('academic_year_id', $duplicateGroup->academic_year_id)
                ->orderByRaw("case when status = 'enrolled' then 1 else 0 end desc")
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get();

            $keeperId = (int) ($rows->first()->id ?? 0);
            $removableIds = $rows
                ->skip(1)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            if ($keeperId === 0 || $removableIds === []) {
                continue;
            }

            foreach (['attendances', 'final_grades', 'conduct_ratings', 'student_departures'] as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                DB::table($table)
                    ->whereIn('enrollment_id', $removableIds)
                    ->update(['enrollment_id' => $keeperId]);
            }

            DB::table('enrollments')
                ->whereIn('id', $removableIds)
                ->delete();
        }
    }

    private function deduplicateStudentScores(): void
    {
        $duplicateGroups = DB::table('student_scores')
            ->select('student_id', 'graded_activity_id', DB::raw('count(*) as duplicate_count'))
            ->groupBy('student_id', 'graded_activity_id')
            ->havingRaw('count(*) > 1')
            ->get();

        foreach ($duplicateGroups as $duplicateGroup) {
            $rows = DB::table('student_scores')
                ->where('student_id', $duplicateGroup->student_id)
                ->where('graded_activity_id', $duplicateGroup->graded_activity_id)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get(['id']);

            $removableIds = $rows
                ->skip(1)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            if ($removableIds === []) {
                continue;
            }

            DB::table('student_scores')
                ->whereIn('id', $removableIds)
                ->delete();
        }
    }
};
