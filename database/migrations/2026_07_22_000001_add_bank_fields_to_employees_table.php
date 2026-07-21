<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // bank_name / account_* có thể đã có từ workflow lương — chỉ thêm phần còn thiếu
            if (! Schema::hasColumn('employees', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('employees', 'account_number') && ! Schema::hasColumn('employees', 'bank_account_number')) {
                $table->string('bank_account_number')->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('employees', 'account_holder') && ! Schema::hasColumn('employees', 'bank_account_holder')) {
                $after = Schema::hasColumn('employees', 'bank_account_number') ? 'bank_account_number' : 'account_number';
                if (Schema::hasColumn('employees', $after)) {
                    $table->string('bank_account_holder')->nullable()->after($after);
                } else {
                    $table->string('bank_account_holder')->nullable();
                }
            }
            if (! Schema::hasColumn('employees', 'address_detail')) {
                $after = Schema::hasColumn('employees', 'account_holder')
                    ? 'account_holder'
                    : (Schema::hasColumn('employees', 'bank_account_holder') ? 'bank_account_holder' : 'bank_name');
                $table->text('address_detail')->nullable()->after($after);
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $drop = [];
            foreach (['bank_account_number', 'bank_account_holder', 'address_detail'] as $col) {
                if (Schema::hasColumn('employees', $col)) {
                    $drop[] = $col;
                }
            }
            // Không drop bank_name nếu workflow lương đang dùng
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
