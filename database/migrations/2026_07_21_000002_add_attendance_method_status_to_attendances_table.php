<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'attendance_method')) {
                $table->string('attendance_method')->default('manual')->after('notes');
            }

            if (!Schema::hasColumn('attendances', 'attendance_status')) {
                $table->string('attendance_status')->nullable()->after('attendance_method');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'attendance_status')) {
                $table->dropColumn('attendance_status');
            }
            if (Schema::hasColumn('attendances', 'attendance_method')) {
                $table->dropColumn('attendance_method');
            }
        });
    }
};
