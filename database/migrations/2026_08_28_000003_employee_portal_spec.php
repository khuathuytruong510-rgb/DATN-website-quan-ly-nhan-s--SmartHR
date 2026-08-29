<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_adjustment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained('attendances')->nullOnDelete();
            $table->date('work_date');
            $table->time('current_check_in')->nullable();
            $table->time('current_check_out')->nullable();
            $table->time('requested_check_in')->nullable();
            $table->time('requested_check_out')->nullable();
            $table->text('reason');
            $table->string('status')->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained('notifications')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->unique(['notification_id', 'user_id']);
        });

        Schema::table('support_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('support_requests', 'hr_reply')) {
                $table->text('hr_reply')->nullable()->after('attachment');
            }
            if (! Schema::hasColumn('support_requests', 'follow_up')) {
                $table->text('follow_up')->nullable()->after('hr_reply');
            }
        });
    }

    public function down(): void
    {
        Schema::table('support_requests', function (Blueprint $table) {
            foreach (['follow_up', 'hr_reply'] as $column) {
                if (Schema::hasColumn('support_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
        Schema::dropIfExists('notification_reads');
        Schema::dropIfExists('attendance_adjustment_requests');
    }
};
