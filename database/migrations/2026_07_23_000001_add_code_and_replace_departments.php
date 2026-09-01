<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chỉ thêm mã phòng ban — KHÔNG xóa/thay dữ liệu departments hiện có.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('departments', 'code')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->string('code', 10)->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('departments', 'code')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->dropColumn('code');
            });
        }
    }
};
