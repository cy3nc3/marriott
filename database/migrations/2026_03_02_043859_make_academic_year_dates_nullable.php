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
        Schema::table('academic_years', function (Blueprint $table) {
            $table->date('start_date')->nullable()->change();
            $table->date('end_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('academic_years')
            ->whereNull('start_date')
            ->update([
                'start_date' => DB::raw('COALESCE(end_date, CURRENT_DATE)'),
            ]);

        DB::table('academic_years')
            ->whereNull('end_date')
            ->update([
                'end_date' => DB::raw('COALESCE(start_date, CURRENT_DATE)'),
            ]);

        Schema::table('academic_years', function (Blueprint $table) {
            $table->date('start_date')->nullable(false)->change();
            $table->date('end_date')->nullable(false)->change();
        });
    }
};
