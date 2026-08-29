<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (Schema::hasTable('attendance_adjustment_requests')) {
            if ($driver === 'mysql') {
                DB::statement("
                    DELETE a1 FROM attendance_adjustment_requests a1
                    INNER JOIN attendance_adjustment_requests a2
                    ON a1.attendance_id = a2.attendance_id
                    AND a1.status = 'pending' AND a2.status = 'pending'
                    AND a1.id > a2.id
                ");
            }

            $this->uniquePending(
                'attendance_adjustment_requests',
                'attendance_id',
                'att_adj_one_pending',
                'pending_attendance_id'
            );
        }

        if (Schema::hasTable('salary_receive_change_requests')) {
            if ($driver === 'mysql') {
                DB::statement("
                    DELETE a1 FROM salary_receive_change_requests a1
                    INNER JOIN salary_receive_change_requests a2
                    ON a1.employee_id = a2.employee_id
                    AND a1.status = 'pending' AND a2.status = 'pending'
                    AND a1.id > a2.id
                ");
            }

            $this->uniquePending(
                'salary_receive_change_requests',
                'employee_id',
                'bank_chg_one_pending',
                'pending_employee_id'
            );
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        foreach ([
            ['attendance_adjustment_requests', 'att_adj_one_pending', 'pending_attendance_id'],
            ['salary_receive_change_requests', 'bank_chg_one_pending', 'pending_employee_id'],
        ] as [$table, $index, $column]) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if ($driver === 'sqlite') {
                DB::statement("DROP INDEX IF EXISTS {$index}");
                continue;
            }

            $indexes = collect(DB::select("SHOW INDEX FROM {$table}"))->pluck('Key_name');
            if ($indexes->contains($index)) {
                DB::statement("ALTER TABLE {$table} DROP INDEX {$index}");
            }
            if (Schema::hasColumn($table, $column)) {
                Schema::table($table, function (Blueprint $blueprint) use ($column) {
                    $blueprint->dropColumn($column);
                });
            }
        }
    }

    private function uniquePending(string $table, string $sourceColumn, string $index, string $generatedColumn): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS {$index} ON {$table} ({$sourceColumn}) WHERE status = 'pending'");

            return;
        }

        if ($driver !== 'mysql') {
            return;
        }

        $indexes = collect(DB::select("SHOW INDEX FROM {$table}"))->pluck('Key_name');
        if ($indexes->contains($index) || Schema::hasColumn($table, $generatedColumn)) {
            return;
        }

        // MySQL 8: unique một pending / resource. Không dùng generated UNSIGNED
        // dựa trên cột FK — InnoDB báo 1215.
        DB::statement("
            CREATE UNIQUE INDEX {$index}
            ON {$table} ((CASE WHEN status = 'pending' THEN {$sourceColumn} END))
        ");
    }
};
