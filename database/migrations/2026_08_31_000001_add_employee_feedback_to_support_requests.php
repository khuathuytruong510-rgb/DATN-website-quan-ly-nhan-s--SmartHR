<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('support_requests', 'employee_feedback')) {
                $table->text('employee_feedback')->nullable()->after('follow_up');
            }
        });
    }

    public function down(): void
    {
        Schema::table('support_requests', function (Blueprint $table) {
            if (Schema::hasColumn('support_requests', 'employee_feedback')) {
                $table->dropColumn('employee_feedback');
            }
        });
    }
};
