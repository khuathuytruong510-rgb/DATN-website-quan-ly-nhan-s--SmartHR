<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = Schema::getIndexes('salary_payments');

        foreach ($indexes as $index) {
            $columns = $index['columns'] ?? [];
            $unique = (bool) ($index['unique'] ?? false);
            if ($columns === ['payroll_id'] && ! $unique) {
                Schema::table('salary_payments', function (Blueprint $table) use ($index) {
                    $table->dropIndex($index['name']);
                });
            }
        }

        $alreadyUnique = collect(Schema::getIndexes('salary_payments'))
            ->contains(fn (array $index) => ($index['columns'] ?? []) === ['payroll_id'] && ($index['unique'] ?? false));

        if (! $alreadyUnique) {
            Schema::table('salary_payments', function (Blueprint $table) {
                $table->unique('payroll_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('salary_payments', function (Blueprint $table) {
            try {
                $table->dropUnique(['payroll_id']);
            } catch (\Throwable) {
            }

            $table->index('payroll_id');
        });
    }
};
