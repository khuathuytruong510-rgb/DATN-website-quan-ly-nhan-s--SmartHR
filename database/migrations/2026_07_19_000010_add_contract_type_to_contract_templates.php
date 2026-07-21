<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contract_templates') && ! Schema::hasColumn('contract_templates', 'contract_type')) {
            Schema::table('contract_templates', function (Blueprint $table) {
                $table->string('contract_type')->nullable()->after('title');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('contract_templates') && Schema::hasColumn('contract_templates', 'contract_type')) {
            Schema::table('contract_templates', function (Blueprint $table) {
                $table->dropColumn('contract_type');
            });
        }
    }
};
