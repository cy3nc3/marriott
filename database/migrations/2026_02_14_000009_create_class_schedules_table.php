<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_assignment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type')->default('academic'); // academic, break, ceremony
            $table->string('label')->nullable(); // e.g. Lunch Break
            $table->string('day'); // Monday, Tuesday, etc.
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
    }
};
