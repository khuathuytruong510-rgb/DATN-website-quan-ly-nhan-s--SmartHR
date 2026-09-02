<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leave_requests')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE leave_requests MODIFY COLUMN type ENUM('maternity','annual','sick','personal','unpaid') NOT NULL DEFAULT 'maternity'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('leave_requests')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::table('leave_requests')->where('type', 'maternity')->update(['type' => 'annual']);
        DB::statement("ALTER TABLE leave_requests MODIFY COLUMN type ENUM('sick','personal','annual','unpaid') NOT NULL DEFAULT 'personal'");
    }
};
