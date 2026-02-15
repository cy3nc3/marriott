<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. 2024-2025
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('upcoming'); // upcoming, ongoing, completed
            $table->string('current_quarter')->default('1'); // 1, 2, 3, 4
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
