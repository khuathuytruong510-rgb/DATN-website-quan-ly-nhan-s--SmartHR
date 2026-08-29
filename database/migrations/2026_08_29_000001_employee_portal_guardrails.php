<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attendance_adjustment_requests') && ! Schema::hasColumn('attendance_adjustment_requests', 'applied_at')) {
            Schema::table('attendance_adjustment_requests', function (Blueprint $table) {
                $table->timestamp('applied_at')->nullable()->after('reviewed_at');
            });
        }

        if (Schema::hasTable('leave_requests')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                if (! Schema::hasColumn('leave_requests', 'cancelled_by')) {
                    $table->foreignId('cancelled_by')->nullable()->after('rejection_reason')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('leave_requests', 'cancelled_at')) {
                    $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
                }
                if (! Schema::hasColumn('leave_requests', 'cancel_reason')) {
                    $table->text('cancel_reason')->nullable()->after('cancelled_at');
                }
            });
        }

        if (Schema::hasTable('payrolls')) {
            Schema::table('payrolls', function (Blueprint $table) {
                if (! Schema::hasColumn('payrolls', 'payout_bank_name')) {
                    $table->string('payout_bank_name')->nullable()->after('payment_method');
                }
                if (! Schema::hasColumn('payrolls', 'payout_account_number')) {
                    $table->string('payout_account_number')->nullable()->after('payout_bank_name');
                }
                if (! Schema::hasColumn('payrolls', 'payout_account_holder')) {
                    $table->string('payout_account_holder')->nullable()->after('payout_account_number');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('attendance_adjustment_requests') && Schema::hasColumn('attendance_adjustment_requests', 'applied_at')) {
            Schema::table('attendance_adjustment_requests', function (Blueprint $table) {
                $table->dropColumn('applied_at');
            });
        }

        if (Schema::hasTable('leave_requests')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                if (Schema::hasColumn('leave_requests', 'cancelled_by')) {
                    $table->dropConstrainedForeignId('cancelled_by');
                }
                foreach (['cancelled_at', 'cancel_reason'] as $column) {
                    if (Schema::hasColumn('leave_requests', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('payrolls')) {
            Schema::table('payrolls', function (Blueprint $table) {
                foreach (['payout_bank_name', 'payout_account_number', 'payout_account_holder'] as $column) {
                    if (Schema::hasColumn('payrolls', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
