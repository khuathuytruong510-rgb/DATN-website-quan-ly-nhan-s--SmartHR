<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_position_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('holder_name');
            $table->string('holder_email')->nullable();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->string('position_name');
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('department_name')->nullable();
            $table->date('started_at');
            $table->date('ended_at')->nullable();
            $table->string('end_reason')->nullable();
            $table->boolean('is_director_role')->default(false);
            $table->string('status')->default('holding');
            $table->string('decision_ref')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'started_at']);
            $table->index(['is_director_role', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_position_histories');
    }
};
