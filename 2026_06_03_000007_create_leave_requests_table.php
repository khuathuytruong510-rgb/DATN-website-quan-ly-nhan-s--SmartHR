<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::create('leave_requests', function (Blueprint $table) {
        $table->id();
        
        // nhan_vien_id: Người tạo đơn (bắt buộc có)
        $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
        
        // nguoi_duyet_id: Người duyệt (để nullable vì khi mới gửi đơn chưa có ai duyệt)
        $table->foreignId('approved_by_id')->nullable()->constrained('employees')->onDelete('set null');
        
        // loai_phep: nam, om, thai_san, hon_nhan, tang, khong_luong
        // Dùng string thay vì enum để sau này dễ mở rộng hệ thống
        $table->string('leave_type')->default('nam'); 
        
        $table->date('start_date');       // ngay_bat_dau
        $table->date('end_date');         // ngay_ket_thuc
        $table->integer('total_days');    // so_ngay nghỉ
        $table->text('reason')->nullable(); // ly_do
        
        // trang_thai: pending, approved, rejected
        $table->string('status')->default('pending'); 
        
        $table->timestamps();
    });
}

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
