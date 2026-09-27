@extends('layouts.app')

@section('content')
<div class="content">
    <div class="page-head">
        <div>
            <h1>Chi tiết lương</h1>
        </div>
        <div class="actions">
            <a href="{{ route('payroll.index', ['month' => $payroll->month, 'year' => $payroll->year]) }}" class="btn">← Danh sách</a>
            <a href="{{ route('payroll.salary_history', $payroll) }}" class="btn">Lịch sử lương</a>

            @php $user = auth()->user(); @endphp
            @if($workflow->actorCanSubmitToDirector($user, $payroll))
                <form method="POST" action="{{ route('payroll.review', $payroll) }}">
                    @csrf
                    <button type="submit" class="btn primary" data-confirm="Gửi phiếu này sang Giám đốc duyệt?">Gửi duyệt</button>
                </form>
            @endif
            @if($workflow->actorCanFinalApprove($user, $payroll))
                <form method="POST" action="{{ route('payroll.approve', $payroll) }}">
                    @csrf
                    <button type="submit" class="btn primary" data-confirm="Phê duyệt cuối bảng lương này?">Phê duyệt cuối</button>
                </form>
            @endif

            @if(auth()->user()->canManageHr() && $workflow->canRemediateIssue($payroll))
                <a href="{{ route('payroll.issues.fix_form', $payroll) }}" class="btn primary">Khắc phục</a>
            @endif

            @if($user->canPayPayroll() && $workflow->canPay($payroll))
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
            @if($payroll->director_approved_at || $payroll->director_approved_name)
            <div style="margin-bottom:14px;">
                <span style="color:#64748b;font-size:13px;">Giám đốc phê duyệt</span>
                <p style="margin:4px 0 0;font-weight:600;">{{ $payroll->directorApproverLabel() }}</p>
            </div>
            @endif
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
            @if(($payroll->confirmation_status === 'issue_reported' || $payroll->status === 'payroll_issue') && $payroll->issue_report)
                <div style="margin-bottom:14px;padding:12px;border-radius:10px;background:#fff7ed;border:1px solid #fed7aa;">
                    <span style="color:#9a3412;font-size:13px;font-weight:600;">Nội dung sự cố</span>
                    <p style="margin:6px 0 0;white-space:pre-wrap;">{{ $payroll->issue_report }}</p>
                    @if($payroll->issue_reported_at)
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
            @php $m = fn ($v) => number_format((float) $v, 0, '.', ',') . ' ₫'; @endphp
            <div style="margin-bottom:10px;display:flex;justify-content:space-between;"><span style="color:#64748b;">Lương cơ bản HĐ</span><strong>{{ $m($payroll->base_salary) }}</strong></div>
            <div style="margin-bottom:10px;display:flex;justify-content:space-between;"><span style="color:#64748b;">Lương theo công</span><strong style="color:#166534;">+ {{ $m($payroll->working_salary) }}</strong></div>
            <div style="margin-bottom:10px;display:flex;justify-content:space-between;"><span style="color:#64748b;">Tăng ca / làm lễ·CN</span><strong style="color:#166534;">+ {{ $m($payroll->overtime_salary) }}</strong></div>
            <div style="margin-bottom:10px;display:flex;justify-content:space-between;"><span style="color:#64748b;">Phụ cấp</span><strong style="color:#166534;">+ {{ $m($payroll->allowance) }}</strong></div>
            <div style="margin-bottom:10px;display:flex;justify-content:space-between;"><span style="color:#64748b;">Thưởng</span><strong style="color:#166534;">+ {{ $m($payroll->bonus) }}</strong></div>
            <div style="margin-bottom:10px;display:flex;justify-content:space-between;"><span style="color:#64748b;">Tổng thu nhập</span><strong>{{ $m($payroll->gross_salary ?: ((float)$payroll->working_salary + (float)$payroll->overtime_salary + (float)$payroll->allowance + (float)$payroll->bonus)) }}</strong></div>
            <div style="margin:12px 0;border-top:1px dashed var(--line);"></div>
            <div style="margin-bottom:8px;display:flex;justify-content:space-between;"><span style="color:#64748b;">BHXH 8%</span><strong style="color:#dc2626;">− {{ $m($payroll->insurance_bhxh) }}</strong></div>
            <div style="margin-bottom:8px;display:flex;justify-content:space-between;"><span style="color:#64748b;">BHYT 1,5%</span><strong style="color:#dc2626;">− {{ $m($payroll->insurance_bhyt) }}</strong></div>
            <div style="margin-bottom:8px;display:flex;justify-content:space-between;"><span style="color:#64748b;">BHTN 1%</span><strong style="color:#dc2626;">− {{ $m($payroll->insurance_bhtn) }}</strong></div>
            <div style="margin-bottom:10px;display:flex;justify-content:space-between;"><span style="color:#64748b;">Tổng BH NLĐ</span><strong style="color:#dc2626;">− {{ $m($payroll->insurance) }}</strong></div>
            <div style="margin-bottom:8px;display:flex;justify-content:space-between;"><span style="color:#64748b;">GTGC bản thân</span><strong>{{ $m($payroll->personal_deduction_amount) }}</strong></div>
            <div style="margin-bottom:8px;display:flex;justify-content:space-between;"><span style="color:#64748b;">GTGC NPT ({{ (int) ($payroll->dependent_count ?? 0) }})</span><strong>{{ $m($payroll->dependent_deduction_amount) }}</strong></div>
            <div style="margin-bottom:8px;display:flex;justify-content:space-between;"><span style="color:#64748b;">TNTT</span><strong>{{ $m($payroll->taxable_income) }}</strong></div>
            <div style="margin-bottom:10px;display:flex;justify-content:space-between;"><span style="color:#64748b;">Thuế TNCN</span><strong style="color:#dc2626;">− {{ $m($payroll->tax) }}</strong></div>
            <div style="margin-bottom:10px;display:flex;justify-content:space-between;"><span style="color:#64748b;">Khấu trừ khác</span><strong style="color:#dc2626;">− {{ $m($payroll->deduction) }}</strong></div>
            @if(($payroll->late_penalty_fee ?? 0) > 0)
            <div style="margin-bottom:10px;display:flex;justify-content:space-between;"><span style="color:#64748b;">Phạt đi muộn</span><strong style="color:#dc2626;">− {{ $m($payroll->late_penalty_fee) }}</strong></div>
            @endif
            <div style="border-top:1px solid var(--line);padding-top:14px;display:flex;justify-content:space-between;align-items:center;">
                <span style="color:#64748b;">Thực nhận</span>
                <strong style="font-size:24px;color:var(--primary);">{{ $m($payroll->total_salary) }}</strong>
            </div>
        </div>
    </div>
</div>
@endsection
