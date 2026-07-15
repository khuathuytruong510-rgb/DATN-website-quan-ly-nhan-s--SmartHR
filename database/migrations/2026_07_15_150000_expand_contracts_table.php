<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'contract_code')) {
                $table->string('contract_code')->nullable()->after('id');
            }
            if (! Schema::hasColumn('contracts', 'contract_type')) {
                $table->string('contract_type')->nullable()->after('title');
            }
            if (! Schema::hasColumn('contracts', 'sign_date')) {
                $table->date('sign_date')->nullable()->after('contract_type');
            }
            if (! Schema::hasColumn('contracts', 'base_salary')) {
                $table->decimal('base_salary', 12, 2)->default(0)->after('salary');
            }
            if (! Schema::hasColumn('contracts', 'allowance')) {
                $table->decimal('allowance', 12, 2)->default(0)->after('base_salary');
            }
            if (! Schema::hasColumn('contracts', 'bonus')) {
                $table->decimal('bonus', 12, 2)->default(0)->after('allowance');
            }
            if (! Schema::hasColumn('contracts', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('bonus');
            }
            if (! Schema::hasColumn('contracts', 'contract_status')) {
                $table->string('contract_status')->default('pending')->after('payment_method');
            }
            if (! Schema::hasColumn('contracts', 'terms')) {
                $table->longText('terms')->nullable()->after('contract_status');
            }
            if (! Schema::hasColumn('contracts', 'signer_id')) {
                $table->foreignId('signer_id')->nullable()->constrained('users')->nullOnDelete()->after('terms');
            }
            if (! Schema::hasColumn('contracts', 'notes')) {
                $table->longText('notes')->nullable()->after('signer_id');
            }
            if (! Schema::hasColumn('contracts', 'document_path')) {
                $table->string('document_path')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('contracts', 'document_name')) {
                $table->string('document_name')->nullable()->after('document_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'signer_id')) {
                $table->dropConstrainedForeignId('signer_id');
            }
            foreach (['contract_code','contract_type','sign_date','base_salary','allowance','bonus','payment_method','contract_status','terms','notes','document_path','document_name'] as $column) {
                if (Schema::hasColumn('contracts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
