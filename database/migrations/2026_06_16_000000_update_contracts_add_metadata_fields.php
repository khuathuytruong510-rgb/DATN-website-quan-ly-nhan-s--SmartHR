<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'salary') && ! Schema::hasColumn('contracts', 'base_salary')) {
                $table->renameColumn('salary', 'base_salary');
            }

            if (! Schema::hasColumn('contracts', 'contract_code')) {
                $table->string('contract_code')->nullable()->after('employee_id');
            }
            if (! Schema::hasColumn('contracts', 'contract_type')) {
                $table->string('contract_type')->nullable()->after('contract_code');
            }
            if (! Schema::hasColumn('contracts', 'signed_date')) {
                $table->date('signed_date')->nullable()->after('contract_type');
            }
            if (! Schema::hasColumn('contracts', 'allowance')) {
                $table->unsignedBigInteger('allowance')->default(0)->after('base_salary');
            }
            if (! Schema::hasColumn('contracts', 'probation_salary')) {
                $table->unsignedBigInteger('probation_salary')->default(0)->after('allowance');
            }
            if (! Schema::hasColumn('contracts', 'company_representative')) {
                $table->string('company_representative')->nullable()->after('probation_salary');
            }
            if (! Schema::hasColumn('contracts', 'signer')) {
                $table->string('signer')->nullable()->after('company_representative');
            }
            if (! Schema::hasColumn('contracts', 'notes')) {
                $table->text('notes')->nullable()->after('signer');
            }
            if (! Schema::hasColumn('contracts', 'pdf_file')) {
                $table->string('pdf_file')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('contracts', 'scan_file')) {
                $table->string('scan_file')->nullable()->after('pdf_file');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'scan_file')) {
                $table->dropColumn('scan_file');
            }
            if (Schema::hasColumn('contracts', 'pdf_file')) {
                $table->dropColumn('pdf_file');
            }
            if (Schema::hasColumn('contracts', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('contracts', 'signer')) {
                $table->dropColumn('signer');
            }
            if (Schema::hasColumn('contracts', 'company_representative')) {
                $table->dropColumn('company_representative');
            }
            if (Schema::hasColumn('contracts', 'probation_salary')) {
                $table->dropColumn('probation_salary');
            }
            if (Schema::hasColumn('contracts', 'allowance')) {
                $table->dropColumn('allowance');
            }
            if (Schema::hasColumn('contracts', 'signed_date')) {
                $table->dropColumn('signed_date');
            }
            if (Schema::hasColumn('contracts', 'contract_type')) {
                $table->dropColumn('contract_type');
            }
            if (Schema::hasColumn('contracts', 'contract_code')) {
                $table->dropColumn('contract_code');
            }
            if (Schema::hasColumn('contracts', 'base_salary') && ! Schema::hasColumn('contracts', 'salary')) {
                $table->renameColumn('base_salary', 'salary');
            }
        });
    }
};
