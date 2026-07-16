<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add calculation fields only if they don't already exist
        if (!Schema::hasColumn('attendances', 'work_hours')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->decimal('work_hours', 8, 2)->nullable()->after('check_out');
            });
        }

        if (!Schema::hasColumn('attendances', 'late_minutes')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->integer('late_minutes')->default(0)->after('work_hours');
            });
        }

        if (!Schema::hasColumn('attendances', 'early_leave_minutes')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->integer('early_leave_minutes')->default(0)->after('late_minutes');
            });
        }

        if (!Schema::hasColumn('attendances', 'overtime_hours')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->decimal('overtime_hours', 8, 2)->default(0)->after('early_leave_minutes');
            });
        }
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'work_hours',
                'late_minutes',
                'early_leave_minutes',
                'overtime_hours',
            ]);
        });
    }
};
