<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->timestamp('sent_at')->nullable()->after('paid_at');
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete()->after('sent_at');
            $table->string('email_status')->default('pending')->after('sent_by');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['sent_by']);
            $table->dropColumn(['sent_at', 'sent_by', 'email_status']);
        });
    }
};