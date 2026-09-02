<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deletion_requests', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->index();
            $table->string('kind')->index();
            $table->unsignedBigInteger('requestable_id');
            $table->string('requestable_type');
            $table->string('name')->nullable();
            $table->json('payload')->nullable();
            $table->text('reason');
            $table->string('status')->default('pending')->index();
            $table->unsignedBigInteger('submitted_by')->nullable()->index();
            $table->unsignedBigInteger('reviewed_by')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->unsignedBigInteger('applied_by')->nullable()->index();
            $table->timestamp('applied_at')->nullable();
            $table->text('cancellation_note')->nullable();
            $table->timestamps();

            $table->index(['requestable_type', 'requestable_id']);

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
            DB::statement('DROP INDEX IF EXISTS del_one_pending');
        } else {
            $indexes = collect(DB::select('SHOW INDEX FROM deletion_requests'))->pluck('Key_name');
            if ($indexes->contains('del_one_pending')) {
                DB::statement('ALTER TABLE deletion_requests DROP INDEX del_one_pending');
            }
        }

        Schema::dropIfExists('deletion_requests');
    }

    private function uniquePending(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS del_one_pending ON deletion_requests (requestable_type, requestable_id) WHERE status = 'pending'");

            return;
        }

        if ($driver !== 'mysql') {
            return;
        }

        DB::statement("
            CREATE UNIQUE INDEX del_one_pending
            ON deletion_requests (
                (CASE WHEN status = 'pending' THEN requestable_type END),
                (CASE WHEN status = 'pending' THEN requestable_id END)
            )
        ");
    }
};