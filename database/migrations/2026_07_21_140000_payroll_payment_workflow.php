<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payrolls') && Schema::getConnection()->getDriverName() === 'mysql') {
            // Mở rộng enum trước, rồi map dữ liệu cũ
            DB::statement("ALTER TABLE payrolls MODIFY COLUMN status ENUM('pending','approved','waiting_confirmation','ready_for_payment','paid') NOT NULL DEFAULT 'pending'");

            DB::table('payrolls')
                ->where('status', 'approved')
                ->where(function ($q) {
                    $q->where('confirmation_status', 'confirmed')
                        ->orWhereNotNull('confirmed_at');
                })
                ->update(['status' => 'ready_for_payment']);

            DB::table('payrolls')
                ->where('status', 'approved')
                ->update(['status' => 'waiting_confirmation']);

            DB::statement("ALTER TABLE payrolls MODIFY COLUMN status ENUM('pending','waiting_confirmation','ready_for_payment','paid') NOT NULL DEFAULT 'pending'");
        }

        Schema::table('payrolls', function (Blueprint $table) {
            if (! Schema::hasColumn('payrolls', 'confirmation_token')) {
                $table->string('confirmation_token', 64)->nullable()->unique()->after('confirmation_deadline');
            }
            if (! Schema::hasColumn('payrolls', 'paid_by')) {
                $table->foreignId('paid_by')->nullable()->after('paid_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('payrolls', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('paid_by');
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('address');
            }
            if (! Schema::hasColumn('employees', 'account_number')) {
                $table->string('account_number')->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('employees', 'account_holder')) {
                $table->string('account_holder')->nullable()->after('account_number');
            }
            if (! Schema::hasColumn('employees', 'qr_image')) {
                $table->string('qr_image')->nullable()->after('account_holder');
            }
        });

        if (! Schema::hasTable('salary_receive_change_requests')) {
            Schema::create('salary_receive_change_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->string('bank_name')->nullable();
                $table->string('account_number')->nullable();
                $table->string('account_holder')->nullable();
                $table->string('qr_image')->nullable();
                $table->text('note')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_note')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('notifications', 'data')) {
                $table->json('data')->nullable()->after('message');
            }
        });

        // Cho phép sender hệ thống
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            try {
                DB::statement('ALTER TABLE notifications MODIFY sender_id BIGINT UNSIGNED NULL');
            } catch (\Throwable) {
                // ignore nếu đã nullable
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payrolls') && Schema::getConnection()->getDriverName() === 'mysql') {
            DB::table('payrolls')->where('status', 'waiting_confirmation')->update(['status' => 'approved']);
            DB::table('payrolls')->where('status', 'ready_for_payment')->update(['status' => 'approved']);
            DB::statement("ALTER TABLE payrolls MODIFY COLUMN status ENUM('pending','approved','paid') NOT NULL DEFAULT 'pending'");
        }

        Schema::table('payrolls', function (Blueprint $table) {
            foreach (['confirmation_token', 'paid_by', 'payment_method'] as $col) {
                if (Schema::hasColumn('payrolls', $col)) {
                    if ($col === 'paid_by') {
                        $table->dropConstrainedForeignId('paid_by');
                    } else {
                        $table->dropColumn($col);
                    }
                }
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            foreach (['bank_name', 'account_number', 'account_holder', 'qr_image'] as $col) {
                if (Schema::hasColumn('employees', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('salary_receive_change_requests');

        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'data')) {
                $table->dropColumn('data');
            }
        });
    }
};
