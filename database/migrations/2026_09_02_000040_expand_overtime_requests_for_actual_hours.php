<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('overtime_requests')) {
            return;
        }

        Schema::table('overtime_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('overtime_requests', 'source')) {
                $table->string('source', 32)->default('requested')->after('employee_id');
            }
            if (! Schema::hasColumn('overtime_requests', 'assigned_by')) {
                $table->foreignId('assigned_by')->nullable()->after('source')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('overtime_requests', 'requested_start')) {
                $table->time('requested_start')->nullable()->after('end_time');
            }
            if (! Schema::hasColumn('overtime_requests', 'requested_end')) {
                $table->time('requested_end')->nullable()->after('requested_start');
            }
            if (! Schema::hasColumn('overtime_requests', 'approved_start')) {
                $table->time('approved_start')->nullable()->after('requested_end');
            }
            if (! Schema::hasColumn('overtime_requests', 'approved_end')) {
                $table->time('approved_end')->nullable()->after('approved_start');
            }
            if (! Schema::hasColumn('overtime_requests', 'actual_start')) {
                $table->time('actual_start')->nullable()->after('approved_end');
            }
            if (! Schema::hasColumn('overtime_requests', 'actual_end')) {
                $table->time('actual_end')->nullable()->after('actual_start');
            }
            if (! Schema::hasColumn('overtime_requests', 'actual_minutes')) {
                $table->unsignedInteger('actual_minutes')->nullable()->after('actual_end');
            }
            if (! Schema::hasColumn('overtime_requests', 'attendance_id')) {
                $table->foreignId('attendance_id')->nullable()->after('actual_minutes')->constrained('attendances')->nullOnDelete();
            }
            if (! Schema::hasColumn('overtime_requests', 'verified_by')) {
                $table->foreignId('verified_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('overtime_requests', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verified_by');
            }
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE overtime_requests MODIFY `status` VARCHAR(32) NOT NULL DEFAULT 'pending'");
        }

        foreach (DB::table('overtime_requests')->orderBy('id')->get() as $row) {
            $updates = [];
            if (empty($row->requested_start) && ! empty($row->start_time)) {
                $updates['requested_start'] = $row->start_time;
            }
            if (empty($row->requested_end) && ! empty($row->end_time)) {
                $updates['requested_end'] = $row->end_time;
            }
            if ($row->status === 'approved') {
                if (empty($row->approved_start) && ! empty($row->start_time)) {
                    $updates['approved_start'] = $row->start_time;
                }
                if (empty($row->approved_end) && ! empty($row->end_time)) {
                    $updates['approved_end'] = $row->end_time;
                }
            }
            if ($updates !== []) {
                DB::table('overtime_requests')->where('id', $row->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('overtime_requests')) {
            return;
        }

        Schema::table('overtime_requests', function (Blueprint $table) {
            foreach ([
                'verified_at',
                'verified_by',
                'attendance_id',
                'actual_minutes',
                'actual_end',
                'actual_start',
                'approved_end',
                'approved_start',
                'requested_end',
                'requested_start',
                'assigned_by',
                'source',
            ] as $column) {
                if (Schema::hasColumn('overtime_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
