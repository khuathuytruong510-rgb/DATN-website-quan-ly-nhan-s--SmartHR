<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Thêm cột phạt đi muộn vào bảng attendances nếu chưa có
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'late_minutes')) {
                $table->integer('late_minutes')->default(0)->after('work_hours');
            }
            if (!Schema::hasColumn('attendances', 'late_penalty_fee')) {
                $table->decimal('late_penalty_fee', 12, 2)->default(0)->after('late_minutes');
            }
        });

        // Thêm cột tổng số phút muộn và tổng khấu trừ phạt vào bảng payrolls
        Schema::table('payrolls', function (Blueprint $table) {
            if (!Schema::hasColumn('payrolls', 'total_late_minutes')) {
                $table->integer('total_late_minutes')->default(0)->after('unpaid_leave_days');
            }
            if (!Schema::hasColumn('payrolls', 'late_deduction')) {
                $table->decimal('late_deduction', 12, 2)->default(0)->after('total_late_minutes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['late_minutes', 'late_penalty_fee']);
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['total_late_minutes', 'late_deduction']);
        });
    }
};