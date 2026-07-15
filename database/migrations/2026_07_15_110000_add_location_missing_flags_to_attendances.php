<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->boolean('check_in_location_missing')->default(false)->after('check_in_notes');
            $table->boolean('check_out_location_missing')->default(false)->after('check_in_location_missing');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['check_in_location_missing', 'check_out_location_missing']);
        });
    }
};
