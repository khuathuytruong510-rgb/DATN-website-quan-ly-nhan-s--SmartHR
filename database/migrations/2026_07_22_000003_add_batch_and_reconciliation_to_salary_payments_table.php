<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('salary_payments', 'batch_id')) {
                $table->unsignedBigInteger('batch_id')->nullable()->index()->after('paid_by');
            }
            if (! Schema::hasColumn('salary_payments', 'reconciliation_status')) {
                $table->string('reconciliation_status')->default('unreconciled')->index()->after('batch_id');
            }
            if (! Schema::hasColumn('salary_payments', 'reconciliation_notes')) {
                $table->text('reconciliation_notes')->nullable()->after('reconciliation_status');
            }
            if (! Schema::hasColumn('salary_payments', 'reconciled_at')) {
                $table->timestamp('reconciled_at')->nullable()->after('reconciliation_notes');
            }
            if (! Schema::hasColumn('salary_payments', 'reconciled_by')) {
                $table->unsignedBigInteger('reconciled_by')->nullable()->after('reconciled_at');
            }
            if (! Schema::hasColumn('salary_payments', 'qr_code')) {
                $table->string('qr_code')->nullable()->after('reconciled_by');
            }
            if (! Schema::hasColumn('salary_payments', 'qr_reference')) {
                $table->string('qr_reference')->nullable()->after('qr_code');
            }
        });

        // Foreign keys (ignore if already exist)
        Schema::table('salary_payments', function (Blueprint $table) {
            try {
                if (Schema::hasColumn('salary_payments', 'batch_id') && Schema::hasTable('salary_payment_batches')) {
                    $table->foreign('batch_id')->references('id')->on('salary_payment_batches')->nullOnDelete();
                }
            } catch (\Throwable) {
            }
            try {
                if (Schema::hasColumn('salary_payments', 'reconciled_by')) {
                    $table->foreign('reconciled_by')->references('id')->on('users')->nullOnDelete();
                }
            } catch (\Throwable) {
            }
        });
    }

    public function down(): void
    {
        Schema::table('salary_payments', function (Blueprint $table) {
            foreach (['batch_id', 'reconciled_by'] as $fk) {
                try {
                    $table->dropForeign([$fk]);
                } catch (\Throwable) {
                }
            }
            $drop = [];
            foreach (['batch_id', 'reconciliation_status', 'reconciliation_notes', 'reconciled_at', 'reconciled_by', 'qr_code', 'qr_reference'] as $col) {
                if (Schema::hasColumn('salary_payments', $col)) {
                    $drop[] = $col;
                }
            }
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
