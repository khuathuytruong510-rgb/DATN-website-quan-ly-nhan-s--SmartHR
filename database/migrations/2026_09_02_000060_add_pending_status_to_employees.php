<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees') || ! Schema::hasColumn('employees', 'status')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE employees MODIFY COLUMN status ENUM('pending', 'active', 'inactive', 'on_leave', 'pending_termination', 'terminated') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('employees') || ! Schema::hasColumn('employees', 'status')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::table('employees')->where('status', 'pending')->update(['status' => 'inactive']);
            DB::statement("ALTER TABLE employees MODIFY COLUMN status ENUM('active', 'inactive', 'on_leave', 'pending_termination', 'terminated') NOT NULL DEFAULT 'active'");
        }
    }
};
