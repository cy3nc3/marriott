<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->string('type')->default('notice')->after('priority');
            $table->string('response_mode')->default('none')->after('type');
            $table->timestamp('event_starts_at')->nullable()->after('publish_at');
            $table->timestamp('event_ends_at')->nullable()->after('event_starts_at');
            $table->timestamp('response_deadline_at')->nullable()->after('event_ends_at');
            $table->timestamp('cancelled_at')->nullable()->after('response_deadline_at');
            $table->foreignId('cancelled_by')
                ->nullable()
                ->after('cancelled_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('cancel_reason')->nullable()->after('cancelled_by');

            $table->index(['type', 'is_active'], 'announcements_type_active_index');
            $table->index(['event_starts_at', 'cancelled_at'], 'announcements_event_start_cancelled_index');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropIndex('announcements_type_active_index');
            $table->dropIndex('announcements_event_start_cancelled_index');
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn([
                'type',
                'response_mode',
                'event_starts_at',
                'event_ends_at',
                'response_deadline_at',
                'cancelled_at',
                'cancel_reason',
            ]);
        });
    }
};
