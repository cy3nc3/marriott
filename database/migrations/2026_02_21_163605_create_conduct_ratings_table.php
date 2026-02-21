<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conduct_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->string('quarter');
            $table->string('maka_diyos', 2)->default('AO');
            $table->string('makatao', 2)->default('AO');
            $table->string('makakalikasan', 2)->default('AO');
            $table->string('makabansa', 2)->default('AO');
            $table->string('remarks', 255)->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            $table->unique(['enrollment_id', 'quarter']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conduct_ratings');
    }
};
