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
        Schema::create('salary_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->nullable()->index();
            $table->unsignedBigInteger('payroll_id')->nullable()->index();
            $table->string('code')->nullable()->index();
            $table->string('period')->nullable();
            $table->date('effective_date')->nullable();
            $table->string('change_type')->nullable();
            $table->decimal('old_salary', 14, 2)->nullable();
            $table->decimal('new_salary', 14, 2)->nullable();
            $table->string('position')->nullable();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->json('allowances')->nullable();
            $table->decimal('rewards', 12, 2)->default(0);
            $table->decimal('deductions', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('insurance', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('document_number')->nullable();
            $table->string('status')->default('pending')->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();

            // foreign keys are optional; adding if referenced tables exist
            if (Schema::hasTable('employees')) {
                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('set null');
            }
            if (Schema::hasTable('payrolls')) {
                $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('set null');
            }
            if (Schema::hasTable('departments')) {
                $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            }
            if (Schema::hasTable('users')) {
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_histories');
    }
};
