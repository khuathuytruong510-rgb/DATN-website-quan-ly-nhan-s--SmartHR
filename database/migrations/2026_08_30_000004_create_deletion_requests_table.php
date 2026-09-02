<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql' && Schema::hasTable('notifications')) {
            DB::statement("ALTER TABLE notifications MODIFY COLUMN target ENUM('employee','hr','director','admin','all') NOT NULL DEFAULT 'employee'");
        }

        Schema::create('deletion_requests', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type'); // employee | department
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label');
            $table->json('snapshot')->nullable();
            $table->text('reason')->nullable();
            $table->string('document_path')->nullable();
            $table->string('document_name')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->unsignedBigInteger('account_user_id')->nullable();
            $table->string('account_email')->nullable();
            $table->timestamp('account_cleared_at')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deletion_requests');

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql' && Schema::hasTable('notifications')) {
            DB::table('notifications')->where('target', 'admin')->update(['target' => 'all']);
            DB::statement("ALTER TABLE notifications MODIFY COLUMN target ENUM('employee','hr','director','all') NOT NULL DEFAULT 'employee'");
        }
    }
};
