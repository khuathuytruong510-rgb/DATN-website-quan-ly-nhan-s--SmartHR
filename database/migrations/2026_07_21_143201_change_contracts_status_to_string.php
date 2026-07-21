<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE contracts MODIFY COLUMN status VARCHAR(50) DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE contracts MODIFY COLUMN status ENUM('active','pending','expired') DEFAULT 'active'");
    }
};
