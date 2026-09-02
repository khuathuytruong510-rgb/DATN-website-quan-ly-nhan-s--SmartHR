<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql' && Schema::hasTable('notifications')) {
            DB::statement("ALTER TABLE notifications MODIFY COLUMN target ENUM('employee','hr','director','all') NOT NULL DEFAULT 'employee'");
        }

        if (Schema::hasTable('overtime_requests') && ! Schema::hasColumn('overtime_requests', 'rejection_reason')) {
            Schema::table('overtime_requests', function ($table) {
                $table->string('rejection_reason')->nullable()->after('approved_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql' && Schema::hasTable('notifications')) {
            DB::table('notifications')->where('target', 'director')->update(['target' => 'hr']);
            DB::statement("ALTER TABLE notifications MODIFY COLUMN target ENUM('employee','hr','all') NOT NULL DEFAULT 'employee'");
        }

        if (Schema::hasTable('overtime_requests') && Schema::hasColumn('overtime_requests', 'rejection_reason')) {
            Schema::table('overtime_requests', function ($table) {
                $table->dropColumn('rejection_reason');
            });
        }
    }
};
