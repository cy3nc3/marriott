<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('or_number_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique();
            $table->string('series_key');
            $table->string('or_number')->unique();
            $table->foreignId('reserved_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('reserved_at');
            $table->timestamp('expires_at');
            $table->timestamp('released_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->foreignId('transaction_id')->nullable()->unique()->constrained('transactions')->nullOnDelete();
            $table->timestamps();

            $table->index(['series_key', 'reserved_by']);
            $table->index(['series_key', 'or_number']);
            $table->index(['series_key', 'expires_at']);
            $table->index(['series_key', 'released_at']);
            $table->index(['series_key', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('or_number_reservations');
    }
};
