<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contract_clauses', function (Blueprint $table) {
            $table->id();
            $table->enum('contract_type', ['internship', 'probation', 'official', 'seasonal'])->index();
            $table->string('section_number')->comment('e.g., 5.1, 5.2, 5.3');
            $table->string('section_title');
            $table->longText('content');
            $table->integer('order')->default(0);
            $table->boolean('is_mandatory')->default(true)->comment('Whether this clause is mandatory for all contracts');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index(['contract_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_clauses');
    }
};
