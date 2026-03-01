<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $this->dropPostgresStatusCheckConstraint();
        } else {
            Schema::table('attendances', function (Blueprint $table) {
                $table->enum('status', [
                    'present',
                    'absent',
                    'late',
                    'excused',
                    'tardy_late_comer',
                    'tardy_cutting_classes',
                ])->default('present')->change();
            });
        }

        DB::table('attendances')
            ->where('status', 'late')
            ->update([
                'status' => 'tardy_late_comer',
            ]);

        DB::table('attendances')
            ->where('status', 'excused')
            ->update([
                'status' => 'absent',
            ]);

        if (DB::getDriverName() === 'pgsql') {
            $this->addPostgresStatusCheckConstraint([
                'present',
                'absent',
                'tardy_late_comer',
                'tardy_cutting_classes',
            ]);
        } else {
            Schema::table('attendances', function (Blueprint $table) {
                $table->enum('status', [
                    'present',
                    'absent',
                    'tardy_late_comer',
                    'tardy_cutting_classes',
                ])->default('present')->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $this->dropPostgresStatusCheckConstraint();
        } else {
            Schema::table('attendances', function (Blueprint $table) {
                $table->enum('status', [
                    'present',
                    'absent',
                    'late',
                    'excused',
                    'tardy_late_comer',
                    'tardy_cutting_classes',
                ])->default('present')->change();
            });
        }

        DB::table('attendances')
            ->whereIn('status', [
                'tardy_late_comer',
                'tardy_cutting_classes',
            ])
            ->update([
                'status' => 'late',
            ]);

        if (DB::getDriverName() === 'pgsql') {
            $this->addPostgresStatusCheckConstraint([
                'present',
                'absent',
                'late',
                'excused',
            ]);
        } else {
            Schema::table('attendances', function (Blueprint $table) {
                $table->enum('status', [
                    'present',
                    'absent',
                    'late',
                    'excused',
                ])->default('present')->change();
            });
        }
    }

    private function dropPostgresStatusCheckConstraint(): void
    {
        DB::statement('ALTER TABLE attendances DROP CONSTRAINT IF EXISTS attendances_status_check');
    }

    /**
     * @param  array<int, string>  $statuses
     */
    private function addPostgresStatusCheckConstraint(array $statuses): void
    {
        $quotedStatuses = implode(', ', array_map(
            fn (string $status): string => "'".str_replace("'", "''", $status)."'",
            $statuses
        ));

        DB::statement("ALTER TABLE attendances ADD CONSTRAINT attendances_status_check CHECK (status IN ({$quotedStatuses}))");
        DB::statement("ALTER TABLE attendances ALTER COLUMN status SET DEFAULT 'present'");
        DB::statement('ALTER TABLE attendances ALTER COLUMN status SET NOT NULL');
    }
};
