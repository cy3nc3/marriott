<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_reminder_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('phase');
            $table->date('target_date');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['announcement_id', 'user_id', 'phase', 'target_date'],
                'announcement_reminder_dispatches_unique'
            );
            $table->index(['target_date', 'phase'], 'announcement_reminder_dispatches_target_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_reminder_dispatches');
    }
};
