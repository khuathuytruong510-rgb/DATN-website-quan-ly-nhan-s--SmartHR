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
       Schema::create('salaries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->integer('month');
            $table->integer('year');

            $table->integer('working_days')->default(0);

            $table->decimal('daily_rate', 12, 2)->default(0);

            $table->decimal('base_salary', 12, 2)->default(0);

            $table->decimal('allowance', 12, 2)->default(500000);

            $table->decimal('total_salary', 12, 2)->default(0);

            $table->timestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};
