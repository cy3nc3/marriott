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
        Schema::table('conduct_ratings', function (Blueprint $table) {
            $table->string('maka_diyos', 2)->nullable()->default(null)->change();
            $table->string('makatao', 2)->nullable()->default(null)->change();
            $table->string('makakalikasan', 2)->nullable()->default(null)->change();
            $table->string('makabansa', 2)->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conduct_ratings', function (Blueprint $table) {
            $table->string('maka_diyos', 2)->default('AO')->nullable(false)->change();
            $table->string('makatao', 2)->default('AO')->nullable(false)->change();
            $table->string('makakalikasan', 2)->default('AO')->nullable(false)->change();
            $table->string('makabansa', 2)->default('AO')->nullable(false)->change();
        });
    }
};
