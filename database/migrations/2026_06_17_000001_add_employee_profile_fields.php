<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('employee_code')->nullable()->unique()->after('id');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('name');
            $table->date('dob')->nullable()->after('gender');
            $table->string('cccd')->nullable()->after('email');
            $table->string('phone')->nullable()->after('cccd');
            $table->text('address')->nullable()->after('phone');
            $table->date('start_date')->nullable()->after('address');
            $table->string('education')->nullable()->after('start_date');
            $table->text('experience')->nullable()->after('education');
            $table->integer('leave_balance')->default(12)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'employee_code',
                'gender',
                'dob',
                'cccd',
                'phone',
                'address',
                'start_date',
                'education',
                'experience',
                'leave_balance',
            ]);
        });
    }
};
