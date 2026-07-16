@extends('layouts.app')

@section('content')
<div class="content">
    <div class="page-head">
        <div>
            <h1>Chi tiết lương</h1>
            <p class="muted">Thông tin lương nhân viên</p>
        </div>
        <div class="actions">
                <a href="{{ route('payroll.edit', $payroll) }}" class="btn primary">Chỉnh sửa</a>
                <a href="{{ route('payroll.salary_history', $payroll) }}" class="btn">Xem lịch sử lương</a>
                <form method="POST" action="{{ route('payroll.send_confirmation', $payroll) }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn" style="background: #f8fafc;">Gửi email xác nhận</button>
                </form>
                @if($payroll->status !== 'paid')
                    @if($payroll->confirmation_status === 'confirmed')
                        <form method="POST" action="{{ route('payroll.approve', $payroll) }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn" style="background: #e0f2fe;">Sẵn sàng thanh toán</button>
                        </form>
                    @endif
                    @if($payroll->status === 'approved')
                        <form method="POST" action="{{ route('payroll.paid', $payroll) }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn primary">Đánh dấu đã thanh toán</button>
                        </form>
                    @endif
                @endif
        </div>
    </div>

    <div class="grid two-cols">
        <div class="card">
            <h3 style="margin-top: 0;">Thông tin chung</h3>
            
            <div style="margin-bottom: 16px;">
                <span style="color: #64748b; font-size: 13px;">Nhân viên</span>
                <p style="margin: 4px 0 0; font-weight: 600;">{{ optional($payroll->employee)->name }}</p>
            </div>

            <div style="margin-bottom: 16px;">
                <span style="color: #64748b; font-size: 13px;">Tháng</span>
                <p style="margin: 4px 0 0; font-weight: 600;">{{ $payroll->month }}</p>
            </div>

            <div style="margin-bottom: 16px;">
                <span style="color: #64748b; font-size: 13px;">Trạng thái</span>
                <p style="margin: 4px 0 0;">
                    @if($payroll->status === 'pending')
                        <span class="badge pending">Chờ duyệt</span>
                    @elseif($payroll->status === 'approved')
                        <span class="badge" style="background: #dbeafe; color: #0369a1;">Sẵn sàng thanh toán</span>
                    @elseif($payroll->status === 'paid')
                        <span class="badge" style="background: #dcfce7; color: #166534;">Đã thanh toán</span>
                    @endif
                </p>
            </div>

            <div style="margin-bottom: 16px;">
                <span style="color: #64748b; font-size: 13px;">Trạng thái xác nhận</span>
                <p style="margin: 4px 0 0;">
                    @if($payroll->confirmation_status === 'confirmed')
                        <span class="badge bg-success">Đã xác nhận</span>
                    @elseif($payroll->confirmation_status === 'issue_reported')
                        <span class="badge bg-warning text-dark">Đã báo cáo lỗi</span>
                    @else
                        <span class="badge bg-secondary">Chưa xác nhận</span>
                    @endif
                </p>
                @if($payroll->confirmation_deadline)
                    <div style="color: #64748b; font-size: 13px; margin-top: 4px;">Hạn xác nhận: {{ $payroll->confirmation_deadline->format('d/m/Y') }}</div>
                @endif
            </div>

            @if($payroll->issue_report)
                <div style="margin-bottom: 16px;">
                    <span style="color: #64748b; font-size: 13px;">Báo cáo sự cố</span>
                    <p style="margin: 4px 0 0; white-space: pre-wrap;">{{ $payroll->issue_report }}</p>
                    @if($payroll->issue_reported_at)
                        <div style="color: #64748b; font-size: 13px; margin-top: 4px;">Đã gửi: {{ $payroll->issue_reported_at->format('d/m/Y H:i') }}</div>
                    @endif
                </div>
            @endif

            <div style="margin-bottom: 16px;">
                <span style="color: #64748b; font-size: 13px;">Trạng thái email</span>
                <p style="margin: 4px 0 0;">
                    @if($payroll->email_status === 'sent')
                        <span class="badge bg-success">Đã gửi</span>
                    @elseif($payroll->email_status === 'failed')
                        <span class="badge bg-danger">Lỗi gửi</span>
                    @else
                        <span class="badge bg-secondary">Chưa gửi</span>
                    @endif
                </p>
                @if($payroll->sent_at)
                    <div style="color: #64748b; font-size: 13px; margin-top: 4px;">Gửi lúc: {{ $payroll->sent_at->format('d/m/Y H:i') }}</div>
                @endif
            </div>

            @if($payroll->paid_at)
                <div>
                    <span style="color: #64748b; font-size: 13px;">Ngày thanh toán</span>
                    <p style="margin: 4px 0 0; font-weight: 600;">{{ $payroll->paid_at->format('d/m/Y H:i') }}</p>
                </div>
            @endif
        </div>

        <div class="card">
            <h3 style="margin-top: 0;">Chi tiết lương</h3>
            
            <div style="margin-bottom: 16px;">
                <span style="color: #64748b; font-size: 13px;">Lương cơ bản</span>
                <p style="margin: 4px 0 0; font-weight: 600; font-size: 18px;">{{ number_format($payroll->base_salary, 0, '.', ',') }} VNĐ</p>
            </div>

            <div style="margin-bottom: 16px;">
                <span style="color: #64748b; font-size: 13px;">Phụ cấp</span>
                <p style="margin: 4px 0 0; font-weight: 600; font-size: 18px; color: #166534;">+ {{ number_format($payroll->allowance ?? 0, 0, '.', ',') }} VNĐ</p>
            </div>

            <div style="margin-bottom: 16px;">
                <span style="color: #64748b; font-size: 13px;">Khấu trừ</span>
                <p style="margin: 4px 0 0; font-weight: 600; font-size: 18px; color: #dc2626;">- {{ number_format($payroll->deduction ?? 0, 0, '.', ',') }} VNĐ</p>
            </div>

            <div style="border-top: 1px solid #e5e7eb; padding-top: 16px; margin-top: 16px;">
                <span style="color: #64748b; font-size: 13px;">Tổng lương</span>
                <p style="margin: 4px 0 0; font-weight: 800; font-size: 24px; color: #2563eb;">{{ number_format($payroll->total_salary, 0, '.', ',') }} VNĐ</p>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top: 20px;">
        <h3 style="margin-top: 0;">Thông tin nhân viên</h3>
        @if($payroll->employee)
            <div class="grid two-cols">
                <div>
                    <span style="color: #64748b; font-size: 13px;">Tên</span>
                    <p style="margin: 4px 0 0 0;">{{ $payroll->employee->name }}</p>
                </div>
                <div>
                    <span style="color: #64748b; font-size: 13px;">Email</span>
                    <p style="margin: 4px 0 0 0;">{{ $payroll->employee->email }}</p>
                </div>
                <div>
                    <span style="color: #64748b; font-size: 13px;">Chức vụ</span>
                    <p style="margin: 4px 0 0 0;">{{ $payroll->employee->position }}</p>
                </div>
                <div>
                    <span style="color: #64748b; font-size: 13px;">Phòng ban</span>
                    <p style="margin: 4px 0 0 0;">{{ optional($payroll->employee->department)->name ?? 'N/A' }}</p>
                </div>
            </div>
        @else
            <p style="color: #64748b;">Không có thông tin nhân viên</p>
        @endif
    </div>
</div>

<style>
    .content { max-width: 900px; }
    .card { background: var(--panel); border: 1px solid var(--line); border-radius: 12px; padding: 24px; }
    .grid { display: grid; gap: 20px; }
    .two-cols { grid-template-columns: 1fr 1fr; }
    .actions { display: flex; gap: 12px; flex-wrap: wrap; }
    .btn { display: inline-flex; align-items: center; padding: 10px 14px; border-radius: 8px; border: none; cursor: pointer; font-weight: 700; text-decoration: none; background: #f8fafc; }
    .btn.primary { background: #2563eb; color: white; }
    .btn.danger { background: #fee2e2; color: #dc2626; }
    .badge { display: inline-flex; border-radius: 999px; padding: 5px 9px; font-size: 12px; font-weight: 700; background: #e0f2fe; color: #0369a1; }
    .badge.pending { background: #fef3c7; color: #92400e; }
</style>
@endsection
