<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grading_rubrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->integer('ww_weight'); // e.g. 40
            $table->integer('pt_weight'); // e.g. 40
            $table->integer('qa_weight'); // e.g. 20
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grading_rubrics');
    }
};
