<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payrolls')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE payrolls MODIFY COLUMN status ENUM('draft','calculated','hr_checked','hr_approved','director_approved','payroll_issue','employee_confirmed','ready_for_payment','paid','pending','hr_reviewed','waiting_confirmation','approved') NOT NULL DEFAULT 'calculated'");
        }

        DB::table('payrolls')->whereIn('status', ['hr_approved', 'hr_reviewed'])->update(['status' => 'hr_checked']);

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE payrolls MODIFY COLUMN status ENUM('draft','calculated','hr_checked','director_approved','payroll_issue','employee_confirmed','ready_for_payment','paid') NOT NULL DEFAULT 'calculated'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('payrolls')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE payrolls MODIFY COLUMN status ENUM('draft','calculated','hr_checked','hr_approved','director_approved','payroll_issue','employee_confirmed','ready_for_payment','paid') NOT NULL DEFAULT 'calculated'");
        }

        DB::table('payrolls')->where('status', 'hr_checked')->update(['status' => 'hr_approved']);

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE payrolls MODIFY COLUMN status ENUM('draft','calculated','hr_approved','director_approved','payroll_issue','employee_confirmed','ready_for_payment','paid') NOT NULL DEFAULT 'calculated'");
        }
    }
};
