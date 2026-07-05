<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('qualification_status')->default('not_qualified');
            $table->boolean('is_let_passer')->default(false);
            $table->string('prc_license_no')->nullable();
            $table->date('license_valid_until')->nullable();
            $table->string('degree')->nullable();
            $table->string('major')->nullable();
            $table->unsignedSmallInteger('professional_education_units')->nullable();
            $table->string('exception_basis')->nullable();
            $table->date('provisional_until')->nullable();
            $table->json('grade_band_eligibility')->nullable();
            $table->json('subject_competency_tags')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('qualification_status');
            $table->index('license_valid_until');
            $table->index('provisional_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_profiles');
    }
};
