<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teacher_subjects', function (Blueprint $table) {
            $table->string('qualification_status')->nullable()->after('subject_id');
            $table->json('eligibility_documents')->nullable()->after('qualification_status');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_subjects', function (Blueprint $table) {
            $table->dropColumn(['qualification_status', 'eligibility_documents']);
        });
    }
};
