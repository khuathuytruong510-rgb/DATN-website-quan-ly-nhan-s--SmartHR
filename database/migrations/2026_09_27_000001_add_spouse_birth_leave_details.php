<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('leave_requests', 'children_count')) {
                $table->unsignedTinyInteger('children_count')->nullable();
            }
            if (! Schema::hasColumn('leave_requests', 'birth_complication')) {
                $table->boolean('birth_complication')->nullable();
            }
            if (! Schema::hasColumn('leave_requests', 'document_path')) {
                $table->string('document_path')->nullable();
            }
            if (! Schema::hasColumn('leave_requests', 'document_name')) {
                $table->string('document_name')->nullable();
            }
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE leave_requests MODIFY COLUMN type ENUM('maternity','spouse_birth','annual','sick','personal','unpaid') NOT NULL DEFAULT 'maternity'");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('leave_requests') && Schema::getConnection()->getDriverName() === 'mysql') {
            DB::table('leave_requests')->where('type', 'spouse_birth')->update(['type' => 'annual']);
            DB::statement("ALTER TABLE leave_requests MODIFY COLUMN type ENUM('maternity','annual','sick','personal','unpaid') NOT NULL DEFAULT 'maternity'");
        }

        Schema::table('leave_requests', function (Blueprint $table): void {
            foreach (['children_count', 'birth_complication', 'document_path', 'document_name'] as $column) {
                if (Schema::hasColumn('leave_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};