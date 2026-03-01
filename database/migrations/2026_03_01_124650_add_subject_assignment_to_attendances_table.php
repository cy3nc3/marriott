<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('subject_assignment_id')
                ->nullable()
                ->after('enrollment_id')
                ->constrained()
                ->nullOnDelete();

            $table->dropUnique(['enrollment_id', 'date']);
            $table->unique(
                ['enrollment_id', 'date', 'subject_assignment_id'],
                'attendances_enrollment_date_assignment_unique'
            );
            $table->index(['subject_assignment_id', 'date'], 'attendances_assignment_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_assignment_date_index');
            $table->dropUnique('attendances_enrollment_date_assignment_unique');
            $table->dropConstrainedForeignId('subject_assignment_id');
            $table->unique(['enrollment_id', 'date']);
        });
    }
};
