<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('enrollments')
            ->where('status', 'partial_payment')
            ->update([
                'status' => 'for_cashier_payment',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
