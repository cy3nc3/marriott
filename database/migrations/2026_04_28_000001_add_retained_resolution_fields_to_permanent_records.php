<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permanent_records', function (Blueprint $table) {
            $table->timestamp('retained_resolved_at')->nullable()->after('conditional_resolution_notes');
            $table->text('retained_resolution_notes')->nullable()->after('retained_resolved_at');
        });
    }

    public function down(): void
    {
        Schema::table('permanent_records', function (Blueprint $table) {
            $table->dropColumn([
                'retained_resolved_at',
                'retained_resolution_notes',
            ]);
        });
    }
};
