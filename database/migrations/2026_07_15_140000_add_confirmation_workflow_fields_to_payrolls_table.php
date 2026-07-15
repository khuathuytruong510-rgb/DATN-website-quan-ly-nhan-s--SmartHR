<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->timestamp('confirmation_deadline')->nullable()->after('confirmation_status');
            $table->text('issue_report')->nullable()->after('confirmation_deadline');
            $table->timestamp('issue_reported_at')->nullable()->after('issue_report');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['confirmation_deadline', 'issue_report', 'issue_reported_at']);
        });
    }
};
