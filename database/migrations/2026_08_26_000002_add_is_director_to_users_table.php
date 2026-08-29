<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_director')) {
            Schema::table('users', function (Blueprint $table) {
                $after = Schema::hasColumn('users', 'is_accountant') ? 'is_accountant' : (Schema::hasColumn('users', 'is_hr') ? 'is_hr' : 'is_admin');
                $table->boolean('is_director')->default(false)->after($after);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_director')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_director');
            });
        }
    }
};
