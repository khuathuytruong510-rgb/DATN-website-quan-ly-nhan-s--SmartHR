@extends('layouts.app')

@section('title', 'Lịch sử thanh toán')

@section('content')
    @include('components.module_header', [
        'title' => 'Lịch sử thanh toán',
        'subtitle' => 'Danh sách các khoản thanh toán lương đã thực hiện.',
    ])

    @php
        $paidCount = $payments->where('status', 'paid')->count();
        $pendingCount = $payments->where('status', 'pending')->count();
    @endphp

    <div class="grid stats" style="margin-bottom:20px;">
        <div class="card">
            <div class="muted">Tổng giao dịch</div>
            <div class="stat-value" style="font-size:32px;">{{ $payments->total() }}</div>
        </div>
        <div class="card">
            <div class="muted">Đã thanh toán</div>
            <div class="stat-value" style="font-size:32px; color:#16a34a;">{{ $paidCount }}</div>
        </div>
        <div class="card">
            <div class="muted">Chờ xử lý</div>
            <div class="stat-value" style="font-size:32px; color:#ea580c;">{{ $pendingCount }}</div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert" style="background:#dcfce7; color:#166534; border-left:4px solid #16a34a;">
            {{ session('success') }}
        </div>
    @endif

    <div class="card" style="padding:0; overflow:hidden;">
        <table>
            <thead>
                <tr>
                    <th>Mã giao dịch</th>
                    <th>Nhân viên</th>
                    <th>Tháng</th>
                    <th style="text-align:right;">Số tiền</th>
                    <th>Phương thức</th>
                    <th>Trạng thái</th>
                    <th>Ngày thanh toán</th>
                    <th style="text-align:right;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td style="font-weight:600;">{{ $payment->code ?? 'N/A' }}</td>
                        <td>{{ $payment->employee->name ?? 'N/A' }}</td>
                        <td>{{ $payment->month }}/{{ $payment->year }}</td>
                        <td style="text-align:right; font-weight:600;">{{ number_format($payment->net ?? $payment->total, 0, '.', ',') }} VNĐ</td>
                        <td>
                            @if($payment->payment_method === 'bank_transfer')
                                <span class="badge" style="background:#e0f2fe; color:#0369a1;">Chuyển khoản</span>
                            @else
                                <span class="badge pending">Tiền mặt</span>
                            @endif
                        </td>
                        <td>
                            @if($payment->status === 'paid')
                                <span class="badge" style="background:#dcfce7; color:#166534;">Đã thanh toán</span>
                            @elseif($payment->status === 'pending')
                                <span class="badge pending">Chờ xử lý</span>
                            @else
                                <span class="badge">{{ $payment->status }}</span>
                            @endif
                        </td>
                        <td>{{ $payment->paid_at ? $payment->paid_at->format('d/m/Y H:i') : '-' }}</td>
                        <td style="text-align:right;">
                            <div class="actions" style="justify-content:flex-end;">
                                <a class="btn link" href="{{ route('payment_center.payments.show', $payment) }}" style="padding:7px 12px; font-size:13px;">Chi tiết</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty">Chưa có lịch sử thanh toán.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination" style="margin-top:18px;">
        {{ $payments->links() }}
    </div>
@endsection
