<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add columns only if they don't already exist to avoid duplicate column errors
        if (!Schema::hasColumn('attendances', 'check_in_latitude')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->decimal('check_in_latitude', 10, 8)->nullable()->after('check_in');
            });
        }

        if (!Schema::hasColumn('attendances', 'check_in_longitude')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->decimal('check_in_longitude', 11, 8)->nullable()->after('check_in_latitude');
            });
        }

        if (!Schema::hasColumn('attendances', 'check_in_location')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->string('check_in_location')->nullable()->after('check_in_longitude');
            });
        }

        if (!Schema::hasColumn('attendances', 'check_in_ip_address')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->string('check_in_ip_address')->nullable()->after('check_in_location');
            });
        }

        if (!Schema::hasColumn('attendances', 'check_out_latitude')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->decimal('check_out_latitude', 10, 8)->nullable()->after('check_out');
            });
        }

        if (!Schema::hasColumn('attendances', 'check_out_longitude')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->decimal('check_out_longitude', 11, 8)->nullable()->after('check_out_latitude');
            });
        }

        if (!Schema::hasColumn('attendances', 'check_out_location')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->string('check_out_location')->nullable()->after('check_out_longitude');
            });
        }

        if (!Schema::hasColumn('attendances', 'check_out_ip_address')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->string('check_out_ip_address')->nullable()->after('check_out_location');
            });
        }

        if (!Schema::hasColumn('attendances', 'check_in_distance')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->decimal('check_in_distance', 8, 2)->nullable()->after('check_out_ip_address')->comment('Distance in meters from office location');
            });
        }

        if (!Schema::hasColumn('attendances', 'check_out_distance')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->decimal('check_out_distance', 8, 2)->nullable()->after('check_in_distance')->comment('Distance in meters from office location');
            });
        }

        if (!Schema::hasColumn('attendances', 'check_in_notes')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->text('check_in_notes')->nullable()->after('check_out_distance');
            });
        }

        if (!Schema::hasColumn('attendances', 'check_out_notes')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->text('check_out_notes')->nullable()->after('check_in_notes');
            });
        }
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'check_in_latitude',
                'check_in_longitude',
                'check_in_location',
                'check_in_ip_address',
                'check_out_latitude',
                'check_out_longitude',
                'check_out_location',
                'check_out_ip_address',
                'check_in_distance',
                'check_out_distance',
                'check_in_notes',
                'check_out_notes',
            ]);
        });
    }
};
