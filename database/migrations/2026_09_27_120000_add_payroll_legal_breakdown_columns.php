<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $columns = [
                'actual_working_salary' => fn (Blueprint $t) => $t->decimal('actual_working_salary', 14, 2)->default(0)->after('working_salary'),
                'paid_leave_salary' => fn (Blueprint $t) => $t->decimal('paid_leave_salary', 14, 2)->default(0)->after('actual_working_salary'),
                'paid_holiday_salary' => fn (Blueprint $t) => $t->decimal('paid_holiday_salary', 14, 2)->default(0)->after('paid_leave_salary'),
                'gross_salary' => fn (Blueprint $t) => $t->decimal('gross_salary', 14, 2)->default(0)->after('bonus'),
                'insurance_base' => fn (Blueprint $t) => $t->decimal('insurance_base', 14, 2)->default(0)->after('insurance'),
                'insurance_bhxh' => fn (Blueprint $t) => $t->decimal('insurance_bhxh', 14, 2)->default(0)->after('insurance_base'),
                'insurance_bhyt' => fn (Blueprint $t) => $t->decimal('insurance_bhyt', 14, 2)->default(0)->after('insurance_bhxh'),
                'insurance_bhtn' => fn (Blueprint $t) => $t->decimal('insurance_bhtn', 14, 2)->default(0)->after('insurance_bhyt'),
                'personal_deduction_amount' => fn (Blueprint $t) => $t->decimal('personal_deduction_amount', 14, 2)->default(0)->after('insurance_bhtn'),
                'dependent_count' => fn (Blueprint $t) => $t->unsignedTinyInteger('dependent_count')->default(0)->after('personal_deduction_amount'),
                'dependent_deduction_amount' => fn (Blueprint $t) => $t->decimal('dependent_deduction_amount', 14, 2)->default(0)->after('dependent_count'),
                'taxable_income' => fn (Blueprint $t) => $t->decimal('taxable_income', 14, 2)->default(0)->after('dependent_deduction_amount'),
            ];

            foreach ($columns as $name => $add) {
                if (! Schema::hasColumn('payrolls', $name)) {
                    $add($table);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $drop = [
                'actual_working_salary',
                'paid_leave_salary',
                'paid_holiday_salary',
                'gross_salary',
                'insurance_base',
                'insurance_bhxh',
                'insurance_bhyt',
                'insurance_bhtn',
                'personal_deduction_amount',
                'dependent_count',
                'dependent_deduction_amount',
                'taxable_income',
            ];
            foreach ($drop as $column) {
                if (Schema::hasColumn('payrolls', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
