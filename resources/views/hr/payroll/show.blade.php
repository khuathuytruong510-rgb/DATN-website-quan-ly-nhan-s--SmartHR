@extends('layouts.app')

@section('content')
<div class="content">
    <div class="page-head">
        <div>
            <h1>Chi tiết lương</h1>
            <p class="muted">{{ optional($payroll->employee)->name }} · Tháng {{ $payroll->month }}/{{ $payroll->year }}</p>
        </div>
        <div class="actions">
            <a href="{{ route('payroll.index', ['month' => $payroll->month, 'year' => $payroll->year]) }}" class="btn">← Danh sách</a>
            <a href="{{ route('payroll.salary_history', $payroll) }}" class="btn">Lịch sử lương</a>

            @php $user = auth()->user(); @endphp
            @if(($user->is_admin || $user->is_hr) && $workflow->canApprove($payroll))
                <form method="POST" action="{{ route('payroll.approve', $payroll) }}">
                    @csrf
                    <button type="submit" class="btn primary" onclick="return confirm('Duyệt bảng lương này?')">Duyệt</button>
                </form>
            @endif

            @if(($user->is_admin || $user->is_hr || $user->is_accountant) && $workflow->canRemediateIssue($payroll))
                <a href="{{ route('payroll.issues.fix_form', $payroll) }}" class="btn primary">Khắc phục</a>
            @endif

            @if(($user->is_admin || $user->is_accountant) && $workflow->canPay($payroll))
                <a href="{{ route('payroll.payment.show', $payroll) }}" class="btn" style="background:#bbf7d0;color:#166534;border:1px solid #86efac;">Thanh toán</a>
            @endif
        </div>
    </div>

    <div class="grid two-cols">
        <div class="card">
            <h3 style="margin-top:0;">Thông tin chung</h3>
            <div style="margin-bottom:14px;">
                <span style="color:#64748b;font-size:13px;">Nhân viên</span>
                <p style="margin:4px 0 0;font-weight:600;">{{ optional($payroll->employee)->name }}</p>
            </div>
            <div style="margin-bottom:14px;">
                <span style="color:#64748b;font-size:13px;">Email</span>
                <p style="margin:4px 0 0;font-weight:600;">{{ optional($payroll->employee)->email }}</p>
            </div>
            <div style="margin-bottom:14px;">
                <span style="color:#64748b;font-size:13px;">Trạng thái</span>
                <p style="margin:4px 0 0;">
                    <span class="badge">{{ $workflow->statusLabel($payroll->status) }}</span>
                </p>
            </div>
            <div style="margin-bottom:14px;">
                <span style="color:#64748b;font-size:13px;">Xác nhận NV</span>
                <p style="margin:4px 0 0;">
                    @if($payroll->confirmation_status === 'confirmed')
                        <span class="badge" style="background:#dcfce7;color:#166534;">Đã xác nhận</span>
                    @elseif($payroll->confirmation_status === 'issue_reported')
                        <span class="badge pending">Báo sai sót</span>
                    @else
                        <span class="badge" style="background:#e2e8f0;color:#475569;">Chưa xác nhận</span>
                    @endif
                </p>
            </div>
            @if($payroll->confirmation_status === 'issue_reported' && $payroll->issue_report)
                <div style="margin-bottom:14px;padding:12px;border-radius:10px;background:#fff7ed;border:1px solid #fed7aa;">
                    <span style="color:#9a3412;font-size:13px;font-weight:600;">Nội dung sự cố</span>
                    <p style="margin:6px 0 0;white-space:pre-wrap;">{{ $payroll->issue_report }}</p>
                    @if($payroll->issue_reported_at)
                        <p class="muted" style="margin:8px 0 0;font-size:12px;">Báo lúc {{ $payroll->issue_reported_at->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
            @endif
            @if($payroll->confirmation_deadline)
                <div style="margin-bottom:14px;">
                    <span style="color:#64748b;font-size:13px;">Hạn xác nhận</span>
                    <p style="margin:4px 0 0;font-weight:600;">{{ $payroll->confirmation_deadline->format('d/m/Y H:i') }}</p>
                </div>
            @endif
            @if($payroll->paid_at)
                <div style="margin-bottom:14px;">
                    <span style="color:#64748b;font-size:13px;">Thanh toán lúc</span>
                    <p style="margin:4px 0 0;font-weight:600;">{{ $payroll->paid_at->format('d/m/Y H:i') }}</p>
                </div>
                <div style="margin-bottom:14px;">
                    <span style="color:#64748b;font-size:13px;">Người thanh toán</span>
                    <p style="margin:4px 0 0;font-weight:600;">{{ optional($payroll->paidByUser)->name ?? '—' }}</p>
                </div>
                <div>
                    <span style="color:#64748b;font-size:13px;">Phương thức</span>
                    <p style="margin:4px 0 0;font-weight:600;">{{ $payroll->payment_method ?? '—' }}</p>
                </div>
            @endif
        </div>

        <div class="card">
            <h3 style="margin-top:0;">Chi tiết số tiền</h3>
            <div style="margin-bottom:12px;display:flex;justify-content:space-between;"><span style="color:#64748b;">Lương cơ bản</span><strong>{{ number_format($payroll->base_salary ?? 0, 0, '.', ',') }} ₫</strong></div>
            <div style="margin-bottom:12px;display:flex;justify-content:space-between;"><span style="color:#64748b;">Phụ cấp</span><strong style="color:#166534;">+ {{ number_format($payroll->allowance ?? 0, 0, '.', ',') }} ₫</strong></div>
            <div style="margin-bottom:12px;display:flex;justify-content:space-between;"><span style="color:#64748b;">Thưởng</span><strong style="color:#166534;">+ {{ number_format($payroll->bonus ?? 0, 0, '.', ',') }} ₫</strong></div>
            <div style="margin-bottom:12px;display:flex;justify-content:space-between;"><span style="color:#64748b;">BHXH</span><strong style="color:#dc2626;">− {{ number_format($payroll->insurance ?? 0, 0, '.', ',') }} ₫</strong></div>
            <div style="margin-bottom:12px;display:flex;justify-content:space-between;"><span style="color:#64748b;">Thuế</span><strong style="color:#dc2626;">− {{ number_format($payroll->tax ?? 0, 0, '.', ',') }} ₫</strong></div>
            <div style="margin-bottom:12px;display:flex;justify-content:space-between;"><span style="color:#64748b;">Khấu trừ</span><strong style="color:#dc2626;">− {{ number_format($payroll->deduction ?? 0, 0, '.', ',') }} ₫</strong></div>
            @if(($payroll->late_penalty_fee ?? 0) > 0)
            <div style="margin-bottom:12px;display:flex;justify-content:space-between;"><span style="color:#64748b;">Phạt đi muộn</span><strong style="color:#dc2626;">− {{ number_format($payroll->late_penalty_fee ?? 0, 0, '.', ',') }} ₫</strong></div>
            @endif
            <div style="border-top:1px solid var(--line);padding-top:14px;display:flex;justify-content:space-between;align-items:center;">
                <span style="color:#64748b;">Thực nhận</span>
                <strong style="font-size:24px;color:var(--primary);">{{ number_format($payroll->total_salary ?? 0, 0, '.', ',') }} ₫</strong>
            </div>
        </div>
    </div>
</div>
@endsection
