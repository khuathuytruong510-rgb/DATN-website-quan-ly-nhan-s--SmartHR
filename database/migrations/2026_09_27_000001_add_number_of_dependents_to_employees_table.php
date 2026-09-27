<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'number_of_dependents')) {
                $table->unsignedTinyInteger('number_of_dependents')
                    ->default(0)
                    ->after('leave_balance')
                    ->comment('Số người phụ thuộc giảm trừ thuế TNCN');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'number_of_dependents')) {
                $table->dropColumn('number_of_dependents');
            }
        });
    }
};
