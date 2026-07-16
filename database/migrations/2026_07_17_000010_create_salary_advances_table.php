<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_advances', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->index();
            $table->unsignedBigInteger('employee_id')->nullable()->index();
            $table->decimal('amount', 14, 2)->default(0);
            $table->text('reason')->nullable();
            $table->date('requested_at')->nullable();
            $table->string('status')->default('pending')->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable()->index();
            $table->timestamp('processed_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('bank')->nullable();
            $table->string('account_holder')->nullable();
            $table->string('account_number')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_deducted')->default(false)->index();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_advances');
    }
};
