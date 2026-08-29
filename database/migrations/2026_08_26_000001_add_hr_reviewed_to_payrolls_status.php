<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payrolls') || Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE payrolls MODIFY COLUMN status ENUM('pending','hr_reviewed','waiting_confirmation','ready_for_payment','paid','approved') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('payrolls') || Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::table('payrolls')->where('status', 'hr_reviewed')->update(['status' => 'pending']);
        DB::statement("ALTER TABLE payrolls MODIFY COLUMN status ENUM('pending','waiting_confirmation','ready_for_payment','paid') NOT NULL DEFAULT 'pending'");
    }
};
