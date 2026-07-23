<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

// 1. salary_payment_batches table
if (!Schema::hasTable('salary_payment_batches')) {
    Schema::create('salary_payment_batches', function (Blueprint $table) {
        $table->id();
        $table->string('code')->unique()->index();
        $table->string('name')->nullable();
        $table->integer('month')->index();
        $table->integer('year')->index();
        $table->integer('total_items')->default(0);
        $table->decimal('total_amount', 14, 2)->default(0);
        $table->decimal('total_paid', 14, 2)->default(0);
        $table->decimal('total_remaining', 14, 2)->default(0);
        $table->string('status')->default('pending')->index();
        $table->unsignedBigInteger('created_by')->nullable();
        $table->unsignedBigInteger('approved_by')->nullable();
        $table->timestamp('approved_at')->nullable();
        $table->timestamp('processed_at')->nullable();
        $table->timestamp('completed_at')->nullable();
        $table->text('notes')->nullable();
        $table->timestamps();
    });
    echo "Created salary_payment_batches\n";
}

// 2. Add batch/reconciliation columns to salary_payments
if (!Schema::hasColumn('salary_payments', 'batch_id')) {
    Schema::table('salary_payments', function (Blueprint $table) {
        $table->unsignedBigInteger('batch_id')->nullable()->index()->after('paid_by');
    });
    echo "Added batch_id to salary_payments\n";
}
if (!Schema::hasColumn('salary_payments', 'reconciliation_status')) {
    Schema::table('salary_payments', function (Blueprint $table) {
        $table->string('reconciliation_status')->default('unreconciled')->index()->after('batch_id');
    });
    echo "Added reconciliation_status to salary_payments\n";
}
if (!Schema::hasColumn('salary_payments', 'reconciliation_notes')) {
    Schema::table('salary_payments', function (Blueprint $table) {
        $table->text('reconciliation_notes')->nullable()->after('reconciliation_status');
    });
    echo "Added reconciliation_notes to salary_payments\n";
}
if (!Schema::hasColumn('salary_payments', 'reconciled_at')) {
    Schema::table('salary_payments', function (Blueprint $table) {
        $table->timestamp('reconciled_at')->nullable()->after('reconciliation_notes');
    });
    echo "Added reconciled_at to salary_payments\n";
}
if (!Schema::hasColumn('salary_payments', 'reconciled_by')) {
    Schema::table('salary_payments', function (Blueprint $table) {
        $table->unsignedBigInteger('reconciled_by')->nullable()->after('reconciled_at');
    });
    echo "Added reconciled_by to salary_payments\n";
}
if (!Schema::hasColumn('salary_payments', 'qr_code')) {
    Schema::table('salary_payments', function (Blueprint $table) {
        $table->string('qr_code')->nullable()->after('reconciled_by');
    });
    echo "Added qr_code to salary_payments\n";
}
if (!Schema::hasColumn('salary_payments', 'qr_reference')) {
    Schema::table('salary_payments', function (Blueprint $table) {
        $table->string('qr_reference')->nullable()->after('qr_code');
    });
    echo "Added qr_reference to salary_payments\n";
}

// 3. Add bank fields to employees
if (!Schema::hasColumn('employees', 'bank_account_number')) {
    Schema::table('employees', function (Blueprint $table) {
        $table->string('bank_account_number')->nullable()->after('bank_name');
    });
    echo "Added bank_account_number to employees\n";
}
if (!Schema::hasColumn('employees', 'bank_account_holder')) {
    Schema::table('employees', function (Blueprint $table) {
        $table->string('bank_account_holder')->nullable()->after('bank_account_number');
    });
    echo "Added bank_account_holder to employees\n";
}
if (!Schema::hasColumn('employees', 'address_detail')) {
    Schema::table('employees', function (Blueprint $table) {
        $table->text('address_detail')->nullable()->after('bank_account_holder');
    });
    echo "Added address_detail to employees\n";
}

// 4. Add allowance to positions
if (!Schema::hasColumn('positions', 'allowance')) {
    Schema::table('positions', function (Blueprint $table) {
        $table->unsignedBigInteger('allowance')->nullable()->after('salary_range_max');
    });
    echo "Added allowance to positions\n";
}

// 5. Add half_day + is_urgent to leave_requests
if (!Schema::hasColumn('leave_requests', 'half_day')) {
    Schema::table('leave_requests', function (Blueprint $table) {
        $table->boolean('half_day')->default(false)->after('type');
    });
    echo "Added half_day to leave_requests\n";
}
if (!Schema::hasColumn('leave_requests', 'is_urgent')) {
    Schema::table('leave_requests', function (Blueprint $table) {
        $table->boolean('is_urgent')->default(false)->after('half_day');
    });
    echo "Added is_urgent to leave_requests\n";
}

// 6. Add contract_type to contract_templates
if (!Schema::hasColumn('contract_templates', 'contract_type')) {
    Schema::table('contract_templates', function (Blueprint $table) {
        $table->string('contract_type')->nullable()->after('name');
    });
    echo "Added contract_type to contract_templates\n";
}

// 7. Create contract_clauses table
if (!Schema::hasTable('contract_clauses')) {
    Schema::create('contract_clauses', function (Blueprint $table) {
        $table->id();
        $table->string('contract_type')->index();
        $table->string('section_number');
        $table->string('section_title');
        $table->longText('content');
        $table->integer('order')->default(0);
        $table->boolean('is_mandatory')->default(true);
        $table->string('status')->default('active');
        $table->timestamps();
    });
    echo "Created contract_clauses\n";
}

echo "\nAll fixes applied!\n";
