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
        if (!Schema::hasColumn('payrolls', 'daily_salary')) {
            Schema::table('payrolls', function (Blueprint $table) {
                $table->decimal('daily_salary', 12, 2)->default(0)->after('base_salary');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn('daily_salary');
        });
    }
};
