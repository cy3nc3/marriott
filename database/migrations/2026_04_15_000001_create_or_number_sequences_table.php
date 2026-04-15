<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('or_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('series_key');
            $table->string('prefix', 20);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('next_number');
            $table->timestamps();

            $table->unique(['series_key', 'year']);
            $table->index(['series_key', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('or_number_sequences');
    }
};
