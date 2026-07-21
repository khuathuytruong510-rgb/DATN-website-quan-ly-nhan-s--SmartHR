<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contract_templates')) {
            Schema::create('contract_templates', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('contract_type')->nullable();
                $table->longText('content')->nullable();
                $table->boolean('is_default')->default(false);
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('contract_logs')) {
            Schema::create('contract_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action');
                $table->string('message')->nullable();
                $table->json('details')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'contract_template_id')) {
                $table->foreignId('contract_template_id')->nullable()->constrained('contract_templates')->nullOnDelete();
            }
            if (! Schema::hasColumn('contracts', 'additional_terms')) {
                $table->longText('additional_terms')->nullable();
            }
            if (! Schema::hasColumn('contracts', 'employee_signed_at')) {
                $table->timestamp('employee_signed_at')->nullable();
            }
            if (! Schema::hasColumn('contracts', 'director_signed_at')) {
                $table->timestamp('director_signed_at')->nullable();
            }
            if (! Schema::hasColumn('contracts', 'workplace')) {
                $table->string('workplace')->nullable();
            }
            if (! Schema::hasColumn('contracts', 'working_schedule')) {
                $table->string('working_schedule')->nullable();
            }
            if (! Schema::hasColumn('contracts', 'benefits')) {
                $table->text('benefits')->nullable();
            }
            if (! Schema::hasColumn('contracts', 'contract_content')) {
                $table->longText('contract_content')->nullable();
            }
            if (! Schema::hasColumn('contracts', 'parent_contract_id')) {
                $table->foreignId('parent_contract_id')->nullable()->constrained('contracts')->nullOnDelete();
            }
            if (! Schema::hasColumn('contracts', 'signed_employee_at')) {
                $table->timestamp('signed_employee_at')->nullable();
            }
            if (! Schema::hasColumn('contracts', 'signed_director_at')) {
                $table->timestamp('signed_director_at')->nullable();
            }
            if (! Schema::hasColumn('contracts', 'salary')) {
                $table->decimal('salary', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('contracts', 'allowance')) {
                $table->decimal('allowance', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('contracts', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });

        if (! DB::table('contract_templates')->where('is_default', true)->exists()) {
            DB::table('contract_templates')->insert([
                'title' => 'Điều khoản hợp đồng lao động mặc định',
                'contract_type' => 'fixed_term',
                'content' => 'Điều 1. Người lao động cam kết làm việc trung thùc...\nĐiều 2. Công ty có quyền thay đổi nội dung phù hợp...',
                'is_default' => true,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_logs');
        Schema::dropIfExists('contract_templates');

        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'contract_template_id')) {
                $table->dropConstrainedForeignId('contract_template_id');
            }
            foreach (['contract_template_id','additional_terms','employee_signed_at','director_signed_at','workplace','working_schedule','benefits','contract_content','parent_contract_id','signed_employee_at','signed_director_at','salary','allowance'] as $column) {
                if (Schema::hasColumn('contracts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
