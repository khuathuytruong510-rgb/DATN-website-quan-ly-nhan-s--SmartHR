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
        Schema::table('payrolls', function (Blueprint $table) {
            $table->integer('year')->nullable()->after('month');
            $table->integer('working_days')->default(0)->after('year');
            $table->integer('required_working_days')->default(26)->after('working_days');
            $table->integer('overtime_days')->default(0)->after('required_working_days');
            $table->decimal('overtime_hours', 8, 2)->default(0)->after('overtime_days');
            $table->decimal('working_salary', 12, 2)->default(0)->after('base_salary');
            $table->decimal('overtime_day_salary', 12, 2)->default(0)->after('working_salary');
            $table->decimal('overtime_hour_salary', 12, 2)->default(0)->after('overtime_day_salary');
            $table->decimal('overtime_salary', 12, 2)->default(0)->after('overtime_hour_salary');
            $table->decimal('bonus', 12, 2)->default(0)->after('allowance');
            $table->decimal('insurance', 12, 2)->default(0)->after('deduction');
            $table->decimal('tax', 12, 2)->default(0)->after('insurance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'year',
                'working_days',
                'required_working_days',
                'overtime_days',
                'overtime_hours',
                'working_salary',
                'overtime_day_salary',
                'overtime_hour_salary',
                'overtime_salary',
                'bonus',
                'insurance',
                'tax',
            ]);
        });
    }
};
