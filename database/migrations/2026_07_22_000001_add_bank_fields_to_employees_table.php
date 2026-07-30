<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('employees', 'bank_name')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('bank_name')->nullable();
            });
        }

        if (! Schema::hasColumn('employees', 'bank_account_number')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('bank_account_number')->nullable();
            });
        }

        if (! Schema::hasColumn('employees', 'bank_account_holder')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('bank_account_holder')->nullable();
            });
        }

        if (! Schema::hasColumn('employees', 'address_detail')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->text('address_detail')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['bank_name', 'bank_account_number', 'bank_account_holder', 'address_detail'] as $col) {
            if (Schema::hasColumn('employees', $col)) {
                Schema::table('employees', function (Blueprint $table) use ($col) {
                    $table->dropColumn($col);
                });
            }
        }
    }
};
