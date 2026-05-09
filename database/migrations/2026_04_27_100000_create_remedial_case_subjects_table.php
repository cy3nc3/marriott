<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remedial_case_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('remedial_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('final_rating', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['remedial_case_id', 'subject_id']);
            $table->index(['assigned_teacher_id', 'academic_year_id']);
            $table->index(['student_id', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remedial_case_subjects');
    }
};
