@extends('layouts.app')

@section('content')
<div class="content">
    <div class="page-head">
        <div>
            <h1><i class="bi bi-receipt me-2"></i>Chi tiết lương</h1>
            <p class="muted">{{ optional($payroll->employee)->name }} · Tháng {{ $payroll->month }}/{{ $payroll->year }}</p>
        </div>
        <div class="actions">
            <a href="{{ route('payroll.index', ['month' => $payroll->month, 'year' => $payroll->year]) }}" class="btn"><i class="bi bi-arrow-left me-1"></i> Danh sách</a>
            <a href="{{ route('payroll.salary_history', $payroll) }}" class="btn"><i class="bi bi-clock-history me-1"></i> Lịch sử lương</a>

            @php $user = auth()->user(); @endphp
            @if(($user->is_admin || $user->is_hr) && $workflow->canApprove($payroll))
                <button class="btn primary" type="button" onclick="confirmApprove()">
                    <i class="bi bi-check-lg me-1"></i> Duyệt
                </button>
            @endif

            @if(($user->is_admin || $user->is_hr || $user->is_accountant) && $workflow->canRemediateIssue($payroll))
                <a href="{{ route('payroll.issues.fix_form', $payroll) }}" class="btn warning">
                    <i class="bi bi-tools me-1"></i> Khắc phục
                </a>
            @endif

            @if(($user->is_admin || $user->is_accountant) && $workflow->canPay($payroll))
                <a href="{{ route('payroll.payment.show', $payroll) }}" class="btn success">
                    <i class="bi bi-wallet2 me-1"></i> Thanh toán
                </a>
            @endif
        </div>
    </div>

    <div class="grid two-cols">
        <div class="card">
            <h3 style="margin-top:0;"><i class="bi bi-person me-2"></i>Thông tin chung</h3>
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
                    <span class="badge bg-primary">{{ $workflow->statusLabel($payroll->status) }}</span>
                </p>
            </div>
            <div style="margin-bottom:14px;">
                <span style="color:#64748b;font-size:13px;">Xác nhận NV</span>
                <p style="margin:4px 0 0;">
                    @if($payroll->confirmation_status === 'confirmed')
                        <span class="badge bg-success">Đã xác nhận</span>
                    @elseif($payroll->confirmation_status === 'issue_reported')
                        <span class="badge bg-warning text-dark">Báo sai sót</span>
                    @else
                        <span class="badge bg-secondary">Chưa xác nhận</span>
                    @endif
                </p>
            </div>
            @if($payroll->confirmation_status === 'issue_reported' && $payroll->issue_report)
                <div style="margin-bottom:14px;padding:12px;border-radius:10px;background:#fff7ed;border:1px solid #fed7aa;">
                    <span style="color:#9a3412;font-size:13px;font-weight:600;"><i class="bi bi-exclamation-triangle me-1"></i> Nội dung sự cố</span>
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
            <h3 style="margin-top:0;"><i class="bi bi-list-check me-2"></i>Chi tiết số tiền</h3>
            <div style="margin-bottom:12px;display:flex;justify-content:space-between;"><span style="color:#64748b;">Lương cơ bản</span><strong>{{ number_format($payroll->base_salary ?? 0, 0, '.', ',') }} ₫</strong></div>
            <div style="margin-bottom:12px;display:flex;justify-content:space-between;"><span style="color:#64748b;">Ngày công thực tế</span><strong>{{ $payroll->working_days ?? 0 }}/{{ $payroll->required_working_days ?? 26 }} ngày</strong></div>
            @if(($payroll->paid_leave_days ?? 0) > 0 || ($payroll->unpaid_leave_days ?? 0) > 0)
                <div style="margin-bottom:12px;display:flex;justify-content:space-between;">
                    <span style="color:#64748b;">Nghỉ phép</span>
                    <div class="text-end">
                        @if(($payroll->paid_leave_days ?? 0) > 0)<span class="text-success">{{ number_format($payroll->paid_leave_days, 1) }} / {{ number_format($payroll->paid_leave_salary, 0, '.', ',') }}</span>@endif
                        @if(($payroll->unpaid_leave_days ?? 0) > 0) <span class="text-danger">{{ number_format($payroll->unpaid_leave_days, 1) }}</span>@endif
                    </div>
                </div>
            @endif
            <div style="margin-bottom:12px;display:flex;justify-content:space-between;"><span style="color:#64748b;">Lương đi làm</span><strong style="color:#166534;">+ {{ number_format($payroll->working_salary ?? 0, 0, '.', ',') }} ₫</strong></div>
            @if(($payroll->overtime_salary ?? 0) > 0)
                <div style="margin-bottom:12px;display:flex;justify-content:space-between;"><span style="color:#64748b;">Tăng ca</span><strong style="color:#166534;">+ {{ number_format($payroll->overtime_salary ?? 0, 0, '.', ',') }} ₫</strong></div>
            @endif
            <div style="margin-bottom:12px;display:flex;justify-content:space-between;"><span style="color:#64748b;">Phụ cấp</span><strong style="color:#166534;">+ {{ number_format($payroll->allowance ?? 0, 0, '.', ',') }} ₫</strong></div>
            <div style="margin-bottom:12px;display:flex;justify-content:space-between;"><span style="color:#64748b;">Thưởng</span><strong style="color:#166534;">+ {{ number_format($payroll->bonus ?? 0, 0, '.', ',') }} ₫</strong></div>
            <div style="margin-bottom:12px;display:flex;justify-content:space-between;"><span style="color:#64748b;">BHXH</span><strong style="color:#dc2626;">- {{ number_format($payroll->insurance ?? 0, 0, '.', ',') }} ₫</strong></div>
            <div style="margin-bottom:12px;display:flex;justify-content:space-between;"><span style="color:#64748b;">Thuế</span><strong style="color:#dc2626;">- {{ number_format($payroll->tax ?? 0, 0, '.', ',') }} ₫</strong></div>
            <div style="margin-bottom:12px;display:flex;justify-content:space-between;"><span style="color:#64748b;">Khấu trừ</span><strong style="color:#dc2626;">- {{ number_format($payroll->deduction ?? 0, 0, '.', ',') }} ₫</strong></div>
            @if(($payroll->late_penalty_fee ?? 0) > 0)
            <div style="margin-bottom:12px;display:flex;justify-content:space-between;"><span style="color:#64748b;">Phạt đi muộn</span><strong style="color:#dc2626;">- {{ number_format($payroll->late_penalty_fee ?? 0, 0, '.', ',') }} ₫</strong></div>
            @endif
            <div style="border-top:1px solid var(--line);padding-top:14px;display:flex;justify-content:space-between;align-items:center;">
                <span style="color:#64748b;">Thực nhận</span>
                <strong style="font-size:24px;color:var(--primary);">{{ number_format($payroll->total_salary ?? 0, 0, '.', ',') }} ₫</strong>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="approveModalLabel">
                    <i class="bi bi-check-circle text-success me-2"></i>Duyệt bảng lương
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn <strong class="text-success">duyệt</strong> bảng lương của <strong>{{ optional($payroll->employee)->name }}</strong>?</p>
                <p class="text-muted mb-0" style="font-size:13px;">Sau khi duyệt, phiếu lương sẽ được gửi cho nhân viên xác nhận.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn" data-bs-dismiss="modal">Hủy</button>
                <form method="POST" action="{{ route('payroll.approve', $payroll) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg me-1"></i> Xác nhận duyệt
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmApprove() {
    var modal = new bootstrap.Modal(document.getElementById('approveModal'));
    modal.show();
}
</script>
@endpush
