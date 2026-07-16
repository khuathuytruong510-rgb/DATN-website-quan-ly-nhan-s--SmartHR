<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       Schema::create('payrolls', function (Blueprint $table) {

    $table->id();

    $table->foreignId('employee_id')
          ->constrained()
          ->cascadeOnDelete();

    $table->unsignedTinyInteger('month');

    $table->unsignedSmallInteger('year');

    $table->decimal('base_salary',12,2);

    $table->decimal('daily_salary',12,2)->default(0);

    $table->integer('required_working_days')->default(26);

    $table->integer('working_days')->default(0);

    $table->integer('paid_leave_days')->default(0);

    $table->integer('unpaid_leave_days')->default(0);

    $table->decimal('working_salary',12,2)->default(0);

    $table->decimal('overtime_hours',8,2)->default(0);

    $table->decimal('overtime_salary',12,2)->default(0);

    $table->decimal('allowance',12,2)->default(0);

    $table->decimal('bonus',12,2)->default(0);

    $table->decimal('deduction',12,2)->default(0);

    $table->decimal('insurance',12,2)->default(0);

    $table->decimal('tax',12,2)->default(0);

    $table->decimal('total_salary',12,2)->default(0);

    $table->enum('status',[
        'pending',
        'approved',
        'paid'
    ])->default('pending');

    $table->timestamp('paid_at')->nullable();

    $table->timestamps();

    $table->unique([
        'employee_id',
        'month',
        'year'
    ]);
});
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};