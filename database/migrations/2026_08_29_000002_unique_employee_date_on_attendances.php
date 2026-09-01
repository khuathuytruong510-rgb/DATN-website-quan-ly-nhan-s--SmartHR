<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendances')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('
                DELETE a1 FROM attendances a1
                INNER JOIN attendances a2
                ON a1.employee_id = a2.employee_id
                AND a1.date = a2.date
                AND a1.id > a2.id
            ');
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->unique(['employee_id', 'date'], 'attendances_employee_id_date_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('attendances')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique('attendances_employee_id_date_unique');
        });
    }
};
