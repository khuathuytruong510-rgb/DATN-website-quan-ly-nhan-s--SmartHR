<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Không tạo bank_account_* trùng với account_* của workflow lương.
 * Chỉ bổ sung address_detail nếu chưa có.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'address_detail')) {
                $after = Schema::hasColumn('employees', 'qr_image')
                    ? 'qr_image'
                    : (Schema::hasColumn('employees', 'account_holder') ? 'account_holder' : 'phone');
                $table->text('address_detail')->nullable()->after($after);
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'address_detail')) {
                $table->dropColumn('address_detail');
            }
        });
    }
};
