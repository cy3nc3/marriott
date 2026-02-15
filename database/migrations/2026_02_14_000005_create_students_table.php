<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('lrn')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('gender')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('contact_number')->nullable();
            $table->text('address')->nullable();
            $table->string('guardian_name')->nullable();
            $table->boolean('is_lis_synced')->default(false);
            $table->boolean('sync_error_flag')->default(false);
            $table->text('sync_error_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
