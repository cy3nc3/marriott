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
            $table->string('status')->default('posted')->after('remarks');
            $table->text('void_reason')->nullable()->after('status');
            $table->timestamp('voided_at')->nullable()->after('void_reason');
            $table->foreignId('voided_by')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();

            $table->index(['status', 'created_at'], 'transactions_status_created_at_index');
            $table->index('voided_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_status_created_at_index');
            $table->dropIndex(['voided_at']);
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn(['status', 'void_reason', 'voided_at']);
        });
    }
};
