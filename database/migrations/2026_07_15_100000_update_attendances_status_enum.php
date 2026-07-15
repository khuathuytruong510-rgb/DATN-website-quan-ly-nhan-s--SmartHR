<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Use raw SQL to modify enum safely for MySQL
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            // Add new enum values used by AttendanceCalculationService
            \DB::statement("ALTER TABLE `attendances` MODIFY `status` ENUM('present','absent','late','leave','leave_early','late_and_leave_early','overtime') NOT NULL DEFAULT 'absent'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            // Revert to original enum (may fail if rows use newer values)
            \DB::statement("ALTER TABLE `attendances` MODIFY `status` ENUM('present','absent','late','leave') NOT NULL DEFAULT 'absent'");
        }
    }
};
