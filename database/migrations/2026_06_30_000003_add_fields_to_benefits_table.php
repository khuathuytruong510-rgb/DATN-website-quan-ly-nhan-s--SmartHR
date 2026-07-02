<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('benefits', function (Blueprint $table) {
            $table->string('code')->nullable()->after('id');
            $table->string('unit')->nullable()->after('amount');
            $table->string('applies_to')->nullable()->after('unit');
            $table->text('condition')->nullable()->after('applies_to');
            $table->enum('application_status', ['active', 'inactive'])->default('active')->after('status');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('approved')->after('application_status');
        });
    }

    public function down(): void
    {
        Schema::table('benefits', function (Blueprint $table) {
            $table->dropColumn(['code', 'unit', 'applies_to', 'condition', 'application_status', 'approval_status']);
        });
    }
};
