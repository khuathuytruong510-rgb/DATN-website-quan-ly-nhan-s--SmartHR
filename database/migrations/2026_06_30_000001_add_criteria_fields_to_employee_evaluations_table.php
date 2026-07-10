<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_evaluations', function (Blueprint $table) {
            $table->unsignedTinyInteger('punctuality')->default(0);
            $table->unsignedTinyInteger('task_completion')->default(0);
            $table->unsignedTinyInteger('quality')->default(0);
            $table->unsignedTinyInteger('technical_skill')->default(0);
            $table->unsignedTinyInteger('responsibility')->default(0);
            $table->unsignedTinyInteger('teamwork')->default(0);
            $table->unsignedTinyInteger('attitude')->default(0);
            $table->unsignedSmallInteger('score_total')->default(0);
            $table->string('classification')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('self_evaluation')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('employee_evaluations', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'punctuality',
                'task_completion',
                'quality',
                'technical_skill',
                'responsibility',
                'teamwork',
                'attitude',
                'score_total',
                'classification',
                'status',
                'approved_by',
                'approved_at',
                'self_evaluation',
            ]);
        });
    }
};
