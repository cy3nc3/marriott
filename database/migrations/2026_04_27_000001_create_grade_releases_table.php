<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->string('quarter');
            $table->foreignId('released_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('released_at');
            $table->timestamps();

            $table->unique(['academic_year_id', 'section_id', 'quarter'], 'grade_releases_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_releases');
    }
};
