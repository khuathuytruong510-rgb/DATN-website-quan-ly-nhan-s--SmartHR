<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {

            $table->string('confirm_token')
                  ->nullable()
                  ->unique()
                  ->after('status');

            $table->timestamp('approved_at')
                  ->nullable()
                  ->after('confirm_token');

        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {

            $table->dropColumn([
                'confirm_token',
                'approved_at'
            ]);

        });
    }
};