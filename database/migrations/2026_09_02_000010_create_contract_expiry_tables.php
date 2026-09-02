<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_expiry_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->string('milestone', 20);
            $table->string('target', 20);
            $table->foreignId('notification_id')->nullable()->constrained('notifications')->nullOnDelete();
            $table->integer('days_remaining')->nullable();
            $table->timestamps();

            $table->unique(['contract_id', 'milestone', 'target'], 'contract_expiry_alerts_unique');
        });

        Schema::create('contract_expiry_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('decided_by')->constrained('users')->cascadeOnDelete();
            $table->string('decision', 20);
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['contract_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_expiry_actions');
        Schema::dropIfExists('contract_expiry_alerts');
    }
};
