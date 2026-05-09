<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            $table->boolean('report_card_submitted')->default(false)->after('email');
            $table->boolean('birth_certificate_submitted')->default(false)->after('report_card_submitted');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            $table->dropColumn([
                'report_card_submitted',
                'birth_certificate_submitted',
            ]);
        });
    }
};

