<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            if (! Schema::hasColumn('payrolls', 'director_approved_by')) {
                $table->foreignId('director_approved_by')->nullable()->after('sent_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('payrolls', 'director_approved_name')) {
                $table->string('director_approved_name')->nullable()->after('director_approved_by');
            }
            if (! Schema::hasColumn('payrolls', 'director_approved_at')) {
                $table->timestamp('director_approved_at')->nullable()->after('director_approved_name');
            }
        });

        if (Schema::hasColumn('payrolls', 'director_approved_by') && Schema::hasColumn('payrolls', 'sent_by')) {
            $approvedStatuses = [
                'director_approved',
                'waiting_confirmation',
                'approved',
                'employee_confirmed',
                'ready_for_payment',
                'paid',
                'payroll_issue',
            ];

            $rows = DB::table('payrolls')
                ->whereNull('director_approved_by')
                ->whereNotNull('sent_by')
                ->whereIn('status', $approvedStatuses)
                ->get(['id', 'sent_by', 'sent_at']);

            $names = DB::table('users')->whereIn('id', $rows->pluck('sent_by')->unique()->filter())->pluck('name', 'id');

            foreach ($rows as $row) {
                DB::table('payrolls')->where('id', $row->id)->update([
                    'director_approved_by' => $row->sent_by,
                    'director_approved_name' => $names[$row->sent_by] ?? null,
                    'director_approved_at' => $row->sent_at,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            if (Schema::hasColumn('payrolls', 'director_approved_by')) {
                $table->dropConstrainedForeignId('director_approved_by');
            }
            foreach (['director_approved_name', 'director_approved_at'] as $column) {
                if (Schema::hasColumn('payrolls', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
