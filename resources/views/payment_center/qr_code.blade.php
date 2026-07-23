@extends('layouts.app')

@section('title', 'Mã QR thanh toán')

@section('content')

@section('breadcrumb')
<li><a href="{{ route('payment_center.dashboard') }}">Trung tâm thanh toán</a></li>
<li><a href="{{ route('payment_center.history') }}">Lịch sử</a></li>
<li>Mã QR</li>
@endsection

<div class="page-head">
    <div>
        <h1>Mã QR thanh toán</h1>
        <p class="muted">Quét mã QR để chuyển khoản thanh toán lương</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('payment_center.payments.show', $salaryPayment) }}">Quay lại phiếu</a>
    </div>
</div>

<div class="grid two-cols">
    <div class="card" style="text-align:center;">
        <h1 style="font-size:18px; margin-bottom:16px;">Mã QR</h1>
        <div style="background:#fff; display:inline-block; padding:20px; border:2px solid #e5e7eb; border-radius:12px; margin-bottom:16px;">
            {!! $qrSvg !!}
        </div>
        <div class="muted" style="margin-top:8px;">Quét mã bằng ứng dụng ngân hàng để chuyển khoản</div>
    </div>

    <div>
        <div class="card" style="margin-bottom:20px;">
            <h1 style="font-size:18px; margin-bottom:12px;">Thông tin thanh toán</h1>
            <table>
                <tr>
                    <td class="muted" style="width:140px;">Nhân viên</td>
                    <td><strong>{{ $salaryPayment->employee->name ?? '-' }}</strong></td>
                </tr>
                <tr>
                    <td class="muted">Mã phiếu</td>
                    <td>{{ $salaryPayment->code }}</td>
                </tr>
                <tr>
                    <td class="muted">Tháng/Năm</td>
                    <td>{{ sprintf('%02d/%04d', $salaryPayment->month, $salaryPayment->year) }}</td>
                </tr>
                <tr>
                    <td class="muted">Số tiền</td>
                    <td><strong style="font-size:20px; color:#16a34a;">{{ number_format($salaryPayment->net, 0) }} VNĐ</strong></td>
                </tr>
                <tr>
                    <td class="muted">Trạng thái</td>
                    <td>
                        @if($salaryPayment->status === 'paid')
                            <span class="badge">Đã thanh toán</span>
                        @else
                            <span class="badge pending">Chờ thanh toán</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        @if(!empty($qrData))
            <div class="card">
                <h1 style="font-size:18px; margin-bottom:12px;">Dữ liệu QR</h1>
                <div style="background:#f8fafc; padding:12px; border-radius:8px; font-family:monospace; font-size:13px; word-break:break-all; white-space:pre-wrap;">{{ is_array($qrData) ? json_encode($qrData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $qrData }}</div>
            </div>
        @endif
    </div>
</div>

@endsection
