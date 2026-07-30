<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contracts')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE contracts MODIFY COLUMN status VARCHAR(50) DEFAULT 'active'");

            return;
        }

        // SQLite / others: recreate status as a plain string column when needed.
        if ($driver === 'sqlite') {
            // SQLite stores ENUM-like columns as TEXT already; no-op is safe.
            return;
        }

        Schema::table('contracts', function ($table) {
            $table->string('status', 50)->default('active')->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('contracts')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE contracts MODIFY COLUMN status ENUM('active','pending','expired') DEFAULT 'active'");
        }
    }
};
