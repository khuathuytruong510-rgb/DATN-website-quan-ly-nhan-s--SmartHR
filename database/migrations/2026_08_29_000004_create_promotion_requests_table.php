<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_requests', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->index();
            $table->unsignedBigInteger('employee_id')->nullable()->index();
            $table->string('change_type')->default('promotion')->index();
            $table->unsignedBigInteger('old_position_id')->nullable();
            $table->string('old_position')->nullable();
            $table->unsignedBigInteger('new_position_id')->nullable();
            $table->string('new_position')->nullable();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->decimal('old_base_salary', 14, 2)->nullable();
            $table->decimal('new_base_salary', 14, 2)->nullable();
            $table->decimal('old_allowance', 14, 2)->nullable();
            $table->decimal('new_allowance', 14, 2)->nullable();
            $table->date('effective_date')->nullable();
            $table->text('reason')->nullable();
            $table->string('document_number')->nullable();
            $table->string('status')->default('pending')->index();
            $table->unsignedBigInteger('submitted_by')->nullable()->index();
            $table->unsignedBigInteger('reviewed_by')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->unsignedBigInteger('applied_by')->nullable()->index();
            $table->timestamp('applied_at')->nullable();
            $table->text('cancellation_note')->nullable();
            $table->timestamps();

            if (Schema::hasTable('employees')) {
                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('set null');
            }
            if (Schema::hasTable('positions')) {
                $table->foreign('old_position_id')->references('id')->on('positions')->onDelete('set null');
                $table->foreign('new_position_id')->references('id')->on('positions')->onDelete('set null');
            }
            if (Schema::hasTable('departments')) {
                $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            }
            if (Schema::hasTable('users')) {
                $table->foreign('submitted_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('applied_by')->references('id')->on('users')->onDelete('set null');
            }
        });

        $this->uniquePending();
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS promo_one_pending');
        } else {
            $indexes = collect(DB::select('SHOW INDEX FROM promotion_requests'))->pluck('Key_name');
            if ($indexes->contains('promo_one_pending')) {
                DB::statement('ALTER TABLE promotion_requests DROP INDEX promo_one_pending');
            }
        }

        Schema::dropIfExists('promotion_requests');
    }

    private function uniquePending(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS promo_one_pending ON promotion_requests (employee_id) WHERE status = 'pending'");

            return;
        }

        if ($driver !== 'mysql') {
            return;
        }

        DB::statement("
            CREATE UNIQUE INDEX promo_one_pending
            ON promotion_requests ((CASE WHEN status = 'pending' THEN employee_id END))
        ");
    }
};