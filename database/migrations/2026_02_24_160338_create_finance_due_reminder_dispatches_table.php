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
        Schema::create('finance_due_reminder_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_due_reminder_rule_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('billing_schedule_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->date('reminder_date');
            $table->foreignId('announcement_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique([
                'finance_due_reminder_rule_id',
                'billing_schedule_id',
                'reminder_date',
            ], 'finance_due_reminder_dispatch_unique');
            $table->index(['reminder_date', 'sent_at'], 'finance_due_reminder_dispatch_date_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_due_reminder_dispatches');
    }
};
