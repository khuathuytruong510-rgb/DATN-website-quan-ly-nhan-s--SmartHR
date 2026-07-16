<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->nullable()->index();
            $table->unsignedBigInteger('payroll_id')->nullable()->index();
            $table->string('code')->nullable()->index();
            $table->integer('month')->nullable()->index();
            $table->integer('year')->nullable()->index();
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('deductions', 14, 2)->default(0);
            $table->decimal('net', 14, 2)->default(0);
            $table->string('payment_method')->nullable()->index();
            $table->string('bank')->nullable();
            $table->string('account_holder')->nullable();
            $table->string('account_number')->nullable();
            $table->string('transaction_code')->nullable();
            $table->string('cash_payer')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending')->index();
            $table->unsignedBigInteger('paid_by')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
    }
};
