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
            'payroll_issue_remediated' => 'Xử lý sự cố bảng lương',
            'attendance_check_in' => 'Chấm công vào',
            'attendance_check_out' => 'Chấm công ra',
            'attendance_adjustment_requested' => 'Yêu cầu điều chỉnh chấm công',
            'attendance_adjustment_approved' => 'Duyệt điều chỉnh chấm công',
            'leave_submitted' => 'Gửi đơn nghỉ phép',
            'leave_cancelled' => 'Hủy đơn nghỉ phép',
            'overtime_submitted' => 'Đăng ký tăng ca',
            'employee_signed', 'contract_signed' => 'Ký hợp đồng',
            'support_submitted' => 'Gửi yêu cầu hỗ trợ',
            'support_approved' => 'HR duyệt yêu cầu hỗ trợ',
            'support_resolved' => 'HR đã xử lý yêu cầu hỗ trợ',
            'support_feedback' => 'Nhân viên phản hồi kết quả hỗ trợ',
            'payroll_calculated' => 'Kế toán tính lương',
            'payroll_recalculated', 'recalculate_payroll' => 'Kế toán tính lại lương',
            'send_payroll', 'payroll_email_sent' => 'Kế toán gửi thông báo lương',
            'payroll_email_failed' => 'Gửi email lương thất bại',
            'payroll_paid' => 'Kế toán thanh toán lương',
            'payroll_period_locked' => 'Chốt kỳ lương',
            'payroll_period_auto_locked' => 'Hệ thống chốt kỳ lương',
            'payroll_period_hr_verified' => 'HR xác nhận kiểm tra nguồn kỳ lương',
            'payroll_period_unlock_requested' => 'HR yêu cầu mở khóa kỳ lương',
            'payroll_period_unlock_rejected' => 'Giám đốc từ chối mở khóa kỳ lương',
            'payroll_period_unlocked' => 'Giám đốc duyệt mở khóa kỳ lương',
            'deletion_requested' => 'Đề nghị nghỉ việc / xóa phòng ban',
            'deletion_approved' => 'Giám đốc duyệt nghỉ việc / xóa phòng ban',
            'deletion_rejected' => 'Giám đốc từ chối nghỉ việc / xóa phòng ban',
            'transfer_requested' => 'Đề nghị chuyển nhân viên',
            'transfer_approved' => 'Giám đốc duyệt chuyển nhân viên',
            'transfer_rejected' => 'Giám đốc từ chối chuyển nhân viên',
            'transfer_feedback' => 'Nhân viên phản hồi điều chuyển',
            'transfer_feedback_reply' => 'HR giải quyết phản hồi điều chuyển',
            'account_deleted_after_employee' => 'Admin xóa tài khoản sau khi nhân viên nghỉ việc',
            'employees_transferred' => 'Chuyển nhân viên sang phòng ban khác',
            'payroll_hr_checked' => 'HR kiểm tra bảng lương',
            'payroll_final_approved' => 'Giám đốc duyệt bảng lương',
            'director_succession' => 'Cập nhật người giữ chức Giám đốc',
            'contract_expiry_handled' => 'Xử lý hợp đồng sắp hết hạn',
            default => $this->humanizeAction($this->action),
        };
    }

    public function detail(): string
    {
        $meta = trim((string) $this->meta);
        if ($meta === '') {
            return '—';
        }

        if ($this->looksLikePlainSentence($meta)) {
            return $this->translatePlainMeta($meta);
        }

        $chunks = preg_split('/\s*;\s*/', $meta) ?: [];
        $parts = [];
        foreach ($chunks as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') {
                continue;
            }
            if (str_contains($chunk, ' → ')) {
                $parts[] = 'Từ '.$this->formatDateToken($chunk);
                continue;
            }
            if (! str_contains($chunk, ':')) {
                $parts[] = $this->translatePlainMeta($chunk);
                continue;
            }
            [$key, $value] = explode(':', $chunk, 2);
            $formatted = $this->formatMetaPair(trim($key), trim($value));
            if ($formatted !== '') {
                $parts[] = $formatted;
            }
        }

        return $parts === [] ? $this->translatePlainMeta($meta) : implode('. ', $parts).'.';
    }

    protected function humanizeAction(string $action): string
    {
        $map = [
            'payroll' => 'lương',
            'period' => 'kỳ',
            'locked' => 'chốt',
            'unlocked' => 'mở khóa',
            'calculated' => 'tính',
            'paid' => 'thanh toán',
        ];
        $words = array_map(
            fn ($word) => $map[$word] ?? $word,
            explode('_', $action)
        );

        return ucfirst(implode(' ', $words));
    }

    protected function looksLikePlainSentence(string $meta): bool
    {
        return ! str_contains($meta, ':') && ! str_contains($meta, ';');
    }

    protected function translatePlainMeta(string $meta): string
    {
        return match (true) {
            strcasecmp($meta, 'User changed password') === 0 => 'Người dùng đã đổi mật khẩu',
            default => $meta,
        };
    }

    protected function formatMetaPair(string $key, string $value): string
    {
        return match ($key) {
            'period' => 'Kỳ lương tháng '.$value,
            'calculated' => 'Đã tính '.$value.' phiếu',
            'skipped' => ((int) $value === 0)
                ? 'Không bỏ qua phiếu nào'
                : 'Bỏ qua '.$value.' phiếu (đã vào vòng duyệt)',
            'reason', 'note', 'prev_issue' => 'Lý do: '.$value,
            'payroll' => $this->payrollDetail((int) $value),
            'method' => 'Hình thức: '.$this->paymentMethodLabel($value),
            'by' => 'Người thực hiện: '.$this->userName((int) $value),
            'ref' => $value === '' ? '' : 'Mã tham chiếu: '.$value,
            'leave' => 'Đơn nghỉ phép #'.$value,
            'overtime' => 'Đơn tăng ca #'.$value,
            'request' => 'Yêu cầu #'.$value,
            'employee' => 'Nhân viên #'.$value,
            'face_profile' => 'Hồ sơ khuôn mặt #'.$value,
            'adjustment' => 'Phiếu điều chỉnh #'.$value,
            'attendance' => 'Bản chấm công #'.$value,
            'applied' => $value === 'yes' ? 'Đã áp dụng vào chấm công' : 'Chưa áp dụng vào chấm công',
            'status_before' => 'Trạng thái trước: '.$value,
            'amount_before' => 'Thực nhận trước: '.$this->money($value),
            'amount_after' => 'Thực nhận sau: '.$this->money($value),
            'from_name' => 'Giám đốc cũ: '.$value,
            'to_name' => 'Giám đốc mới: '.$value,
            'effective' => 'Ngày hiệu lực: '.$this->formatDateToken($value),
            'decision' => $value === '' ? '' : 'Số quyết định: '.$value,
            default => $this->humanizeAction($key).': '.$this->formatDateToken($value),
        };
    }

    protected function payrollDetail(int $id): string
    {
        $payroll = Payroll::query()->with('employee')->find($id);
        if (! $payroll) {
            return 'Phiếu lương #'.$id;
        }

        $name = optional($payroll->employee)->name ?: 'nhân viên';

        return 'Phiếu lương #'.$id.' của '.$name.' (tháng '.$payroll->display_month.')';
    }

    protected function userName(int $id): string
    {
        return optional(User::query()->find($id))->name ?: '#'.$id;
    }

    protected function paymentMethodLabel(string $method): string
    {
        return match ($method) {
            'cash' => 'tiền mặt',
            'bank', 'bank_transfer' => 'chuyển khoản',
            default => $method,
        };
    }

    protected function money(string $value): string
    {
        if (! is_numeric($value)) {
            return $value;
        }

        return number_format((float) $value, 0, ',', '.').' ₫';
    }

    protected function formatDateToken(string $value): string
    {
        if (preg_match('/^(\d{4}-\d{2}-\d{2})(?:\s*→\s*(\d{4}-\d{2}-\d{2}))?$/', $value, $m)) {
            $start = \Carbon\Carbon::parse($m[1])->format('d/m/Y');
            if (! empty($m[2])) {
                return $start.' → '.\Carbon\Carbon::parse($m[2])->format('d/m/Y');
            }

            return $start;
        }

        return $value;
    }
}
