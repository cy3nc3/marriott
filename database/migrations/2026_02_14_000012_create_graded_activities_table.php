<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('graded_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_assignment_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // WW, PT, QA
            $table->string('quarter'); // 1, 2, 3, 4
            $table->string('title'); // Quiz 1, Project A
            $table->integer('max_score');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('graded_activities');
    }
};
