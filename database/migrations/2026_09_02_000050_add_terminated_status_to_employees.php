<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql' && Schema::hasColumn('employees', 'status')) {
            DB::statement("ALTER TABLE employees MODIFY COLUMN status ENUM('active', 'inactive', 'on_leave', 'pending_termination', 'terminated') NOT NULL DEFAULT 'active'");
        }

        if (! Schema::hasColumn('employees', 'terminated_at')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->date('terminated_at')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        if (Schema::hasColumn('employees', 'terminated_at')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('terminated_at');
            });
        }

        if (Schema::getConnection()->getDriverName() === 'mysql' && Schema::hasColumn('employees', 'status')) {
            DB::table('employees')->whereIn('status', ['pending_termination', 'terminated'])->update(['status' => 'inactive']);
            DB::statement("ALTER TABLE employees MODIFY COLUMN status ENUM('active', 'inactive', 'on_leave') NOT NULL DEFAULT 'active'");
        }
    }
};
