<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['allowance', 'insurance', 'bonus', 'other'])->default('other');
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('benefits', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['approved_by']);
        });

        Schema::dropIfExists('benefits');
    }
};
