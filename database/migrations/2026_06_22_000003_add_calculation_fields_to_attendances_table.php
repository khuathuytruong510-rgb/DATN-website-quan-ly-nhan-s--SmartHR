<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Add calculation fields
            $table->decimal('work_hours', 8, 2)->nullable()->after('check_out');
            $table->integer('late_minutes')->default(0)->after('work_hours');
            $table->integer('early_leave_minutes')->default(0)->after('late_minutes');
            $table->decimal('overtime_hours', 8, 2)->default(0)->after('early_leave_minutes');
            
            // Modify status enum to include more statuses
            // First, we need to drop the old enum and create new one
        });
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
