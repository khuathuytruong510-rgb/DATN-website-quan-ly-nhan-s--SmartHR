<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('face_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('face_profiles', 'status')) {
                $table->string('status', 20)->default('pending')->after('face_image');
            }
            if (! Schema::hasColumn('face_profiles', 'pending_face_embedding')) {
                $table->text('pending_face_embedding')->nullable()->after('status');
            }
            if (! Schema::hasColumn('face_profiles', 'pending_face_image')) {
                $table->longText('pending_face_image')->nullable()->after('pending_face_embedding');
            }
            if (! Schema::hasColumn('face_profiles', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('pending_face_image')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('face_profiles', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('face_profiles', 'rejection_reason')) {
                $table->string('rejection_reason')->nullable()->after('approved_at');
            }
        });

        DB::table('face_profiles')
            ->whereNotNull('face_embedding')
            ->where('face_embedding', '!=', '')
            ->update(['status' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('face_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('face_profiles', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }
            foreach (['status', 'pending_face_embedding', 'pending_face_image', 'approved_at', 'rejection_reason'] as $column) {
                if (Schema::hasColumn('face_profiles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
