<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('leave_requests', 'is_second_child')) {
                $table->boolean('is_second_child')->nullable()->after('birth_complication');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('leave_requests', 'is_second_child')) {
                $table->dropColumn('is_second_child');
            }
        });
    }
};
