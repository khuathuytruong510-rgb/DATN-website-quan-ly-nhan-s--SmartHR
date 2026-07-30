<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('payrolls', 'late_penalty_fee')) {
            Schema::table('payrolls', function (Blueprint $table) {
                $table->decimal('late_penalty_fee', 12, 2)->default(0);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payrolls', 'late_penalty_fee')) {
            Schema::table('payrolls', function (Blueprint $table) {
                $table->dropColumn('late_penalty_fee');
            });
        }
    }
};
