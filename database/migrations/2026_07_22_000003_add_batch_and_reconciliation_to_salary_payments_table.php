<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('batch_id')->nullable()->index()->after('paid_by');
            $table->string('reconciliation_status')->default('unreconciled')->index()->after('batch_id');
            $table->text('reconciliation_notes')->nullable()->after('reconciliation_status');
            $table->timestamp('reconciled_at')->nullable()->after('reconciliation_notes');
            $table->unsignedBigInteger('reconciled_by')->nullable()->after('reconciled_at');
            $table->string('qr_code')->nullable()->after('reconciled_by');
            $table->string('qr_reference')->nullable()->after('qr_code');

            $table->foreign('batch_id')->references('id')->on('salary_payment_batches')->nullOnDelete();
            $table->foreign('reconciled_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('salary_payments', function (Blueprint $table) {
            $table->dropForeign(['batch_id', 'reconciled_by']);
            $table->dropColumn(['batch_id', 'reconciliation_status', 'reconciliation_notes', 'reconciled_at', 'reconciled_by', 'qr_code', 'qr_reference']);
        });
    }
};
