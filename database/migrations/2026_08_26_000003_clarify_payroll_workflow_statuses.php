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
            DB::statement("ALTER TABLE payrolls MODIFY COLUMN status ENUM(
                'draft',
                'calculated',
                'hr_approved',
                'director_approved',
                'payroll_issue',
                'employee_confirmed',
                'ready_for_payment',
                'paid',
                'pending',
                'hr_reviewed',
                'waiting_confirmation',
                'approved'
            ) NOT NULL DEFAULT 'calculated'");
        }

        DB::table('payrolls')->whereIn('status', ['pending', 'draft'])->update(['status' => 'calculated']);
        DB::table('payrolls')->where('status', 'hr_reviewed')->update(['status' => 'hr_approved']);
        DB::table('payrolls')->whereIn('status', ['waiting_confirmation', 'approved'])->update(['status' => 'director_approved']);
        DB::table('payrolls')
            ->where('confirmation_status', 'issue_reported')
            ->where('status', '!=', 'paid')
            ->update(['status' => 'payroll_issue']);
        DB::table('payrolls')
            ->where('status', 'ready_for_payment')
            ->where('confirmation_status', 'confirmed')
            ->update(['status' => 'employee_confirmed']);

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE payrolls MODIFY COLUMN status ENUM(
                'draft',
                'calculated',
                'hr_approved',
                'director_approved',
                'payroll_issue',
                'employee_confirmed',
                'ready_for_payment',
                'paid'
            ) NOT NULL DEFAULT 'calculated'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('payrolls')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE payrolls MODIFY COLUMN status ENUM(
                'draft',
                'calculated',
                'hr_approved',
                'director_approved',
                'payroll_issue',
                'employee_confirmed',
                'ready_for_payment',
                'paid',
                'pending',
                'hr_reviewed',
                'waiting_confirmation',
                'approved'
            ) NOT NULL DEFAULT 'pending'");
        }

        DB::table('payrolls')->where('status', 'calculated')->update(['status' => 'pending']);
        DB::table('payrolls')->where('status', 'hr_approved')->update(['status' => 'hr_reviewed']);
        DB::table('payrolls')->where('status', 'director_approved')->update(['status' => 'waiting_confirmation']);
        DB::table('payrolls')->where('status', 'payroll_issue')->update(['status' => 'waiting_confirmation']);
        DB::table('payrolls')->where('status', 'employee_confirmed')->update(['status' => 'ready_for_payment']);

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE payrolls MODIFY COLUMN status ENUM('pending','hr_reviewed','waiting_confirmation','ready_for_payment','paid','approved') NOT NULL DEFAULT 'pending'");
        }
    }
};
