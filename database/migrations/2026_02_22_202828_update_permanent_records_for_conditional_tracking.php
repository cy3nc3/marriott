<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateGroups = DB::table('permanent_records')
            ->select('student_id', 'academic_year_id', DB::raw('MAX(id) as keep_id'))
            ->groupBy('student_id', 'academic_year_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $duplicateGroup) {
            DB::table('permanent_records')
                ->where('student_id', $duplicateGroup->student_id)
                ->where('academic_year_id', $duplicateGroup->academic_year_id)
                ->where('id', '!=', $duplicateGroup->keep_id)
                ->delete();
        }

        Schema::table('permanent_records', function (Blueprint $table) {
            $table->unsignedTinyInteger('failed_subject_count')->default(0)->after('status');
            $table->timestamp('conditional_resolved_at')->nullable()->after('failed_subject_count');
            $table->text('conditional_resolution_notes')->nullable()->after('conditional_resolved_at');
            $table->unique(['student_id', 'academic_year_id'], 'permanent_records_student_year_unique');
        });
    }

    public function down(): void
    {
        Schema::table('permanent_records', function (Blueprint $table) {
            $table->dropUnique('permanent_records_student_year_unique');
            $table->dropColumn([
                'failed_subject_count',
                'conditional_resolved_at',
                'conditional_resolution_notes',
            ]);
        });
    }
};
