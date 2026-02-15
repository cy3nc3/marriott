<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('description'); // e.g. Downpayment, Oct Installment
            $table->date('due_date');
            $table->decimal('amount_due', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('status')->default('unpaid'); // unpaid, partially_paid, paid
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_schedules');
    }
};
