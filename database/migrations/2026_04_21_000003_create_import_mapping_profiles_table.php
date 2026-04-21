<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_mapping_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('module');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('profile_name');
            $table->json('header_map');
            $table->json('parsing_rules')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_mapping_profiles');
    }
};
