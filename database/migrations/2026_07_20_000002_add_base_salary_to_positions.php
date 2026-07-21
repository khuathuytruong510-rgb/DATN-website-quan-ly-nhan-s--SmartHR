<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('positions', 'base_salary')) {
            Schema::table('positions', function (Blueprint $table) {
                $after = Schema::hasColumn('positions', 'allowance') ? 'allowance' : null;
                if ($after) {
                    $table->unsignedBigInteger('base_salary')->default(0)->after($after);
                } else {
                    $table->unsignedBigInteger('base_salary')->default(0);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('positions', 'base_salary')) {
            Schema::table('positions', function (Blueprint $table) {
                $table->dropColumn('base_salary');
            });
        }
    }
};
