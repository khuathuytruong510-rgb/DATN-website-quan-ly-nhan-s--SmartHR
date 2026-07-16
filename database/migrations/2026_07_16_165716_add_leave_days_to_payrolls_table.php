<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('payrolls', 'paid_leave_days')) {
            Schema::table('payrolls', function (Blueprint $table) {
                $table->integer('paid_leave_days')->default(0)->after('working_days');
            });
        }

        if (!Schema::hasColumn('payrolls', 'unpaid_leave_days')) {
            Schema::table('payrolls', function (Blueprint $table) {
                $table->integer('unpaid_leave_days')->default(0)->after('paid_leave_days');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['paid_leave_days', 'unpaid_leave_days']);
        });
    }
};
