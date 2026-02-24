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
        Schema::table('transactions', function (Blueprint $table) {
            $table->text('refund_reason')->nullable()->after('voided_by');
            $table->timestamp('refunded_at')->nullable()->after('refund_reason');
            $table->foreignId('refunded_by')
                ->nullable()
                ->after('refunded_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->text('reissue_reason')->nullable()->after('refunded_by');
            $table->timestamp('reissued_at')->nullable()->after('reissue_reason');
            $table->foreignId('reissued_by')
                ->nullable()
                ->after('reissued_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('reissued_transaction_id')
                ->nullable()
                ->after('reissued_by')
                ->constrained('transactions')
                ->nullOnDelete();

            $table->index('refunded_at');
            $table->index('reissued_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['refunded_at']);
            $table->dropIndex(['reissued_at']);
            $table->dropConstrainedForeignId('reissued_transaction_id');
            $table->dropConstrainedForeignId('reissued_by');
            $table->dropConstrainedForeignId('refunded_by');
            $table->dropColumn([
                'refund_reason',
                'refunded_at',
                'reissue_reason',
                'reissued_at',
            ]);
        });
    }
};
