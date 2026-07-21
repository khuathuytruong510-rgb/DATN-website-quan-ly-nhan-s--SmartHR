<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'allowed_unpaid_leave_days_per_month')) {
                $table->unsignedTinyInteger('allowed_unpaid_leave_days_per_month')->default(1)->after('benefits');
            }
            if (! Schema::hasColumn('contracts', 'allowed_makeup_attendance_per_month')) {
                $table->unsignedTinyInteger('allowed_makeup_attendance_per_month')->default(3)->after('allowed_unpaid_leave_days_per_month');
            }
            if (! Schema::hasColumn('contracts', 'allowed_maternity_leave_days')) {
                $table->unsignedSmallInteger('allowed_maternity_leave_days')->default(180)->after('allowed_makeup_attendance_per_month');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            foreach (['allowed_unpaid_leave_days_per_month', 'allowed_makeup_attendance_per_month', 'allowed_maternity_leave_days'] as $column) {
                if (Schema::hasColumn('contracts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
