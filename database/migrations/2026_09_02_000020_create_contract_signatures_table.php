<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('signer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('signer_role', 32);
            $table->string('document_hash', 64);
            $table->text('signature_value')->nullable();
            $table->string('signed_document_path')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('status', 32)->default('pending');
            $table->string('provider', 64)->default('mock');
            $table->string('provider_transaction_id')->nullable();
            $table->text('verify_note')->nullable();
            $table->timestamps();

            $table->index(['contract_id', 'signer_role']);
        });

        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'content_locked_at')) {
                $table->timestamp('content_locked_at')->nullable()->after('director_signed_at');
            }
            if (! Schema::hasColumn('contracts', 'canonical_document_path')) {
                $table->string('canonical_document_path')->nullable()->after('content_locked_at');
            }
            if (! Schema::hasColumn('contracts', 'document_hash')) {
                $table->string('document_hash', 64)->nullable()->after('canonical_document_path');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_signatures');

        Schema::table('contracts', function (Blueprint $table) {
            foreach (['content_locked_at', 'canonical_document_path', 'document_hash'] as $column) {
                if (Schema::hasColumn('contracts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
