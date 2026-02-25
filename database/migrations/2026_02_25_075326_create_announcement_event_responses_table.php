<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_event_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('response');
            $table->timestamp('responded_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['announcement_id', 'user_id'], 'announcement_event_responses_unique');
            $table->index(['announcement_id', 'response'], 'announcement_event_response_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_event_responses');
    }
};
