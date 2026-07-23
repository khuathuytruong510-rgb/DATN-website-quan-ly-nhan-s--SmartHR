@extends('layouts.app')

@section('title', 'Chi tiết thanh toán #' . $salaryPayment->code)

@section('content')

@section('breadcrumb')
<li><a href="{{ route('payment_center.dashboard') }}">Trung tâm thanh toán</a></li>
<li><a href="{{ route('payment_center.history') }}">Lịch sử</a></li>
<li>Phiếu #{{ $salaryPayment->code }}</li>
@endsection

<div class="page-head">
    <div>
        <h1>Phiếu thanh toán #{{ $salaryPayment->code }}</h1>
        <p class="muted">Chi tiết phiếu thanh toán lương nhân viên</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('payment_center.history') }}">Quay lại</a>
        @if($salaryPayment->status === 'pending')
            <a class="btn primary" href="{{ route('payment_center.qr_code', $salaryPayment) }}">Xem QR</a>
        @endif
    </div>
</div>

<div class="grid two-cols">

    <div>
        <div class="card" style="margin-bottom:20px;">
            <h1 style="font-size:18px; margin-bottom:12px;">Thông tin nhân viên</h1>
            <table>
                <tr>
                    <td class="muted" style="width:160px;">Họ tên</td>
                    <td><strong>{{ $salaryPayment->employee->name ?? '-' }}</strong></td>
                </tr>
                <tr>
                    <td class="muted">Mã nhân viên</td>
                    <td>{{ $salaryPayment->employee->employee_code ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="muted">Phòng ban</td>
                    <td>{{ $salaryPayment->employee->department->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="muted">Chức vụ</td>
                    <td>{{ $salaryPayment->employee->position ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="muted">Email</td>
                    <td>{{ $salaryPayment->employee->email ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <div class="card" style="margin-bottom:20px;">
            <h1 style="font-size:18px; margin-bottom:12px;">Chi tiết lương</h1>
            <table>
                <tr>
                    <td class="muted" style="width:160px;">Tháng/Năm</td>
                    <td>{{ sprintf('%02d/%04d', $salaryPayment->month, $salaryPayment->year) }}</td>
                </tr>
                <tr>
                    <td class="muted">Tổng lương</td>
                    <td>{{ number_format($salaryPayment->total, 0) }} VNĐ</td>
                </tr>
                <tr>
                    <td class="muted">Khấu trừ</td>
                    <td style="color:#dc2626;">{{ number_format($salaryPayment->deductions, 0) }} VNĐ</td>
                </tr>
                <tr>
                    <td class="muted">Thực lĩnh</td>
                    <td><strong style="font-size:20px; color:#16a34a;">{{ number_format($salaryPayment->net, 0) }} VNĐ</strong></td>
                </tr>
            </table>
        </div>

        <div class="card" style="margin-bottom:20px;">
            <h1 style="font-size:18px; margin-bottom:12px;">Thông tin thanh toán</h1>
            <table>
                <tr>
                    <td class="muted" style="width:160px;">Trạng thái</td>
                    <td>
                        @if($salaryPayment->status === 'paid')
                            <span class="badge">Đã thanh toán</span>
                        @elseif($salaryPayment->status === 'pending')
                            <span class="badge pending">Chờ thanh toán</span>
                        @else
                            <span class="badge" style="background:#fee2e2;color:#dc2626;">Đã hủy</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="muted">Hình thức</td>
                    <td>
                        @if($salaryPayment->payment_method === 'bank_transfer')
                            <span class="badge" style="background:#dbeafe;color:#1e40af;">Chuyển khoản</span>
                        @elseif($salaryPayment->payment_method === 'cash')
                            <span class="badge" style="background:#fef3c7;color:#92400e;">Tiền mặt</span>
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                </tr>
                @if($salaryPayment->payment_method === 'bank_transfer')
                    <tr>
                        <td class="muted">Ngân hàng</td>
                        <td>{{ $salaryPayment->bank ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="muted">Số tài khoản</td>
                        <td>{{ $salaryPayment->account_number ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="muted">Chủ tài khoản</td>
                        <td>{{ $salaryPayment->account_holder ?? '-' }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="muted">Ngày thanh toán</td>
                    <td>{{ $salaryPayment->paid_at ? $salaryPayment->paid_at->format('d/m/Y H:i') : '—' }}</td>
                </tr>
                <tr>
                    <td class="muted">Người thanh toán</td>
                    <td>{{ $salaryPayment->paidBy->name ?? '-' }}</td>
                </tr>
            </table>
        </div>

        @if($salaryPayment->batch)
            <div class="card" style="margin-bottom:20px;">
                <h1 style="font-size:18px; margin-bottom:12px;">Lô thanh toán liên quan</h1>
                <table>
                    <tr>
                        <td class="muted" style="width:160px;">Mã lô</td>
                        <td><a href="{{ route('payment_center.batches.show', $salaryPayment->batch) }}">{{ $salaryPayment->batch->code }}</a></td>
                    </tr>
                    <tr>
                        <td class="muted">Tên lô</td>
                        <td>{{ $salaryPayment->batch->name }}</td>
                    </tr>
                </table>
            </div>
        @endif

        @if($salaryPayment->reconciliation_status)
            <div class="card" style="margin-bottom:20px;">
                <h1 style="font-size:18px; margin-bottom:12px;">Đối soát</h1>
                <table>
                    <tr>
                        <td class="muted" style="width:160px;">Trạng thái</td>
                        <td>
                            @if($salaryPayment->reconciliation_status === 'reconciled')
                                <span class="badge">Đã đối soát</span>
                            @elseif($salaryPayment->reconciliation_status === 'discrepancy')
                                <span class="badge" style="background:#fee2e2;color:#dc2626;">Chênh lệch</span>
                            @else
                                <span class="muted">Chưa đối soát</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="muted">Người đối soát</td>
                        <td>{{ $salaryPayment->reconciledBy->name ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        @endif
    </div>

    <div>
        <div class="card" style="margin-bottom:20px; text-align:center;">
            <div class="muted" style="margin-bottom:8px;">Tổng thực lĩnh</div>
            <div style="font-size:36px; font-weight:800; color:#16a34a;">{{ number_format($salaryPayment->net, 0) }}</div>
            <div class="muted">VNĐ</div>
        </div>

        @if($salaryPayment->payroll)
            <div class="card" style="margin-bottom:20px;">
                <h1 style="font-size:18px; margin-bottom:12px;">Bảng lương liên quan</h1>
                <a class="btn primary" href="{{ route('payroll.show', $salaryPayment->payroll) }}" style="width:100%; text-align:center;">Xem bảng lương</a>
            </div>
        @endif
    </div>

</div>

@if($salaryPayment->logs && $salaryPayment->logs->count())
    <div class="card" style="margin-top:20px;">
        <h1 style="font-size:18px; margin-bottom:12px;">Nhật ký hoạt động</h1>
        <table>
            <thead>
                <tr>
                    <th>Hành động</th>
                    <th>Thực hiện bởi</th>
                    <th>Thời gian</th>
                    <th>Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                @foreach($salaryPayment->logs->reverse() as $log)
                    <tr>
                        <td><strong>{{ $log->action }}</strong></td>
                        <td>{{ $log->user->name ?? '-' }}</td>
                        <td class="muted">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="muted">{{ $log->notes ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@endsection
