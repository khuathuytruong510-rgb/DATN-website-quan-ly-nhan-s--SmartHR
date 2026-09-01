<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'action', 'meta'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return match ($this->action) {
            'change_password' => 'Đổi mật khẩu',
            'payroll_confirmed', 'payroll_auto_ready' => 'Xác nhận bảng lương',
            'payroll_issue_reported' => 'Báo sai sót bảng lương',
            'attendance_check_in' => 'Chấm công vào',
            'attendance_check_out' => 'Chấm công ra',
            'attendance_adjustment_requested' => 'Yêu cầu điều chỉnh chấm công',
            'leave_submitted' => 'Gửi đơn nghỉ phép',
            'leave_cancelled' => 'Hủy đơn nghỉ phép',
            'overtime_submitted' => 'Đăng ký tăng ca',
            'employee_signed', 'contract_signed' => 'Ký hợp đồng',
            'support_submitted' => 'Gửi yêu cầu hỗ trợ',
            'payroll_calculated' => 'Kế toán tính lương',
            'payroll_recalculated' => 'Kế toán tính lại lương',
            'recalculate_payroll' => 'Kế toán tính lại lương',
            'payroll_paid' => 'Kế toán thanh toán lương',
            'payroll_email_sent' => 'Kế toán gửi thông báo lương',
            'payroll_email_failed' => 'Gửi email lương thất bại',
            'payroll_period_locked' => 'HR chốt kỳ lương',
            'payroll_period_unlocked' => 'HR mở khóa kỳ lương',
            'promotion_created' => 'HR tạo đề xuất thăng chức/tăng lương',
            'promotion_approved' => 'Giám đốc duyệt đề xuất thăng chức/tăng lương',
            'promotion_rejected' => 'Giám đốc từ chối đề xuất thăng chức/tăng lương',
            'promotion_cancelled' => 'Hủy đề xuất thăng chức/tăng lương',
            'promotion_applied' => 'Đã áp dụng thăng chức/tăng lương',
            default => str_replace('_', ' ', $this->action),
        };
    }
}
