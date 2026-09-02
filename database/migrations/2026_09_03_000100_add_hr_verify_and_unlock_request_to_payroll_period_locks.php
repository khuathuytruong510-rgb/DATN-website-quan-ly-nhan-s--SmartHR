<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_period_locks', function (Blueprint $table) {
            if (! Schema::hasColumn('payroll_period_locks', 'hr_verified_at')) {
                $table->timestamp('hr_verified_at')->nullable()->after('unlock_reason');
            }
            if (! Schema::hasColumn('payroll_period_locks', 'hr_verified_by')) {
                $table->foreignId('hr_verified_by')->nullable()->after('hr_verified_at')
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('payroll_period_locks', 'unlock_request_status')) {
                $table->string('unlock_request_status', 20)->nullable()->after('hr_verified_by');
            }
            if (! Schema::hasColumn('payroll_period_locks', 'unlock_requested_at')) {
                $table->timestamp('unlock_requested_at')->nullable()->after('unlock_request_status');
            }
            if (! Schema::hasColumn('payroll_period_locks', 'unlock_requested_by')) {
                $table->foreignId('unlock_requested_by')->nullable()->after('unlock_requested_at')
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('payroll_period_locks', 'unlock_request_reason')) {
                $table->string('unlock_request_reason', 500)->nullable()->after('unlock_requested_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_period_locks', function (Blueprint $table) {
            foreach (['hr_verified_by', 'unlock_requested_by'] as $fk) {
                if (Schema::hasColumn('payroll_period_locks', $fk)) {
                    $table->dropConstrainedForeignId($fk);
                }
            }
            foreach (['hr_verified_at', 'unlock_request_status', 'unlock_requested_at', 'unlock_request_reason'] as $col) {
                if (Schema::hasColumn('payroll_period_locks', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
