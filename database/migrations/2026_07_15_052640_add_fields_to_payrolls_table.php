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
        $columns = [
            'year', 'working_days', 'required_working_days', 'overtime_days', 'overtime_hours',
            'working_salary', 'overtime_day_salary', 'overtime_hour_salary', 'overtime_salary',
            'bonus', 'insurance', 'tax'
        ];

        foreach ($columns as $col) {
            if (!Schema::hasColumn('payrolls', $col)) {
                Schema::table('payrolls', function (Blueprint $table) use ($col) {
                    switch ($col) {
                        case 'year':
                            $table->integer('year')->nullable()->after('month');
                            break;
                        case 'working_days':
                            $table->integer('working_days')->default(0)->after('year');
                            break;
                        case 'required_working_days':
                            $table->integer('required_working_days')->default(26)->after('working_days');
                            break;
                        case 'overtime_days':
                            $table->integer('overtime_days')->default(0)->after('required_working_days');
                            break;
                        case 'overtime_hours':
                            $table->decimal('overtime_hours', 8, 2)->default(0)->after('overtime_days');
                            break;
                        case 'working_salary':
                            $table->decimal('working_salary', 12, 2)->default(0)->after('base_salary');
                            break;
                        case 'overtime_day_salary':
                            $table->decimal('overtime_day_salary', 12, 2)->default(0)->after('working_salary');
                            break;
                        case 'overtime_hour_salary':
                            $table->decimal('overtime_hour_salary', 12, 2)->default(0)->after('overtime_day_salary');
                            break;
                        case 'overtime_salary':
                            $table->decimal('overtime_salary', 12, 2)->default(0)->after('overtime_hour_salary');
                            break;
                        case 'bonus':
                            $table->decimal('bonus', 12, 2)->default(0)->after('allowance');
                            break;
                        case 'insurance':
                            $table->decimal('insurance', 12, 2)->default(0)->after('deduction');
                            break;
                        case 'tax':
                            $table->decimal('tax', 12, 2)->default(0)->after('insurance');
                            break;
                    }
                });
            }
        }
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
