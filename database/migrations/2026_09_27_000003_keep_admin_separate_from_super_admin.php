<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_super_admin')) {
            return;
        }

        // Undo only the bootstrap promotion previously made by migration 000002.
        $bootstrapAdminId = DB::table('users')
            ->where('is_admin', true)
            ->where('is_super_admin', true)
            ->orderBy('id')
            ->value('id');

        if ($bootstrapAdminId) {
            DB::table('users')->where('id', $bootstrapAdminId)->update(['is_super_admin' => false]);
        }
    }

    public function down(): void
    {
        // Intentionally leave the Admin role unchanged on rollback.
    }
};