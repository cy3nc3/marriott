<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->foreignId('academic_year_id')
                ->nullable()
                ->after('grade_level_id')
                ->constrained('academic_years')
                ->nullOnDelete();

            $table->index(['academic_year_id', 'grade_level_id'], 'fees_academic_year_grade_level_index');
        });

        $activeAcademicYearId = (int) (DB::table('academic_years')
            ->where('status', 'ongoing')
            ->value('id')
            ?? DB::table('academic_years')
                ->orderByDesc('start_date')
                ->value('id')
            ?? 0);

        if ($activeAcademicYearId > 0) {
            DB::table('fees')
                ->whereNull('academic_year_id')
                ->update(['academic_year_id' => $activeAcademicYearId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->dropIndex('fees_academic_year_grade_level_index');
            $table->dropConstrainedForeignId('academic_year_id');
        });
    }
};
