<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('holidays')) {
            Schema::create('holidays', function (Blueprint $table) {
                $table->id();
                $table->date('date')->unique();
                $table->string('name');
                $table->string('type', 32)->default('national');
                $table->boolean('is_paid')->default(true);
                $table->decimal('work_rate', 4, 2)->default(3);
                $table->string('source', 64)->default('bldd_112');
                $table->boolean('is_substitute')->default(false);
                $table->timestamps();
            });
        } else {
            Schema::table('holidays', function (Blueprint $table) {
                if (! Schema::hasColumn('holidays', 'is_paid')) {
                    $table->boolean('is_paid')->default(true);
                }
                if (! Schema::hasColumn('holidays', 'work_rate')) {
                    $table->decimal('work_rate', 4, 2)->default(3);
                }
                if (! Schema::hasColumn('holidays', 'source')) {
                    $table->string('source', 64)->default('bldd_112');
                }
                if (! Schema::hasColumn('holidays', 'is_substitute')) {
                    $table->boolean('is_substitute')->default(false);
                }
            });
        }

        Schema::table('payrolls', function (Blueprint $table) {
            if (! Schema::hasColumn('payrolls', 'paid_holiday_days')) {
                $table->decimal('paid_holiday_days', 6, 2)->default(0);
            }
            if (! Schema::hasColumn('payrolls', 'holiday_work_salary')) {
                $table->decimal('holiday_work_salary', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('payrolls', 'weekly_rest_work_salary')) {
                $table->decimal('weekly_rest_work_salary', 12, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            foreach (['paid_holiday_days', 'holiday_work_salary', 'weekly_rest_work_salary'] as $column) {
                if (Schema::hasColumn('payrolls', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
