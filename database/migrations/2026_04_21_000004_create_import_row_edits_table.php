<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_row_edits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_row_id')->constrained('import_batch_rows')->cascadeOnDelete();
            $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('before_payload');
            $table->json('after_payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_row_edits');
    }
};
