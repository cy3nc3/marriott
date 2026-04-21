<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batch_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_index');
            $table->json('raw_payload');
            $table->json('normalized_payload')->nullable();
            $table->json('validation_errors')->nullable();
            $table->json('duplicate_flags')->nullable();
            $table->string('classification')->nullable();
            $table->string('action')->default('pending');
            $table->boolean('is_unresolved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batch_rows');
    }
};
