@extends('layouts.app')

@section('title', 'Trung tâm thanh toán - Dashboard')

@section('content')

@section('breadcrumb')
<li><a href="{{ route('payment_center.dashboard') }}">Trung tâm thanh toán</a></li>
<li>Dashboard</li>
@endsection

<div class="page-head">
    <div>
        <h1>Trung tâm thanh toán</h1>
        <p class="muted">Tổng quan về tình hình thanh toán lương</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('payment_center.export') }}">Xuất báo cáo</a>
    </div>
</div>

<div class="grid stats">
    <div class="card">
        <div class="muted">Tổng đã thanh toán</div>
        <div class="stat-value" style="color:#16a34a">{{ number_format($stats['total_paid'] ?? 0, 0) }} VNĐ</div>
    </div>
    <div class="card">
        <div class="muted">Tổng chờ thanh toán</div>
        <div class="stat-value" style="color:#d97706">{{ number_format($stats['total_pending'] ?? 0, 0) }} VNĐ</div>
    </div>
    <div class="card">
        <div class="muted">Tổng còn lại</div>
        <div class="stat-value" style="color:#dc2626">{{ number_format($stats['total_remaining'] ?? 0, 0) }} VNĐ</div>
    </div>
</div>

<div class="card" style="margin-bottom:20px;">
    <form method="GET" action="{{ route('payment_center.dashboard') }}" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
        <div class="field" style="margin-bottom:0; min-width:140px;">
            <label>Tháng</label>
            <select name="month">
                @for($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}" {{ request('month', $month ?? now()->month) == $i ? 'selected' : '' }}>
                        Tháng {{ $i }}
                    </option>
                @endfor
            </select>
        </div>
        <div class="field" style="margin-bottom:0; min-width:140px;">
            <label>Năm</label>
            <select name="year">
                @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                    <option value="{{ $y }}" {{ request('year', $year ?? now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </div>
        <button class="btn primary" type="submit">Lọc</button>
        <a class="btn" href="{{ route('payment_center.dashboard') }}">Đặt lại</a>
    </form>
</div>

<div class="grid two-cols">

    <div class="card">
        <div class="page-head" style="margin-bottom:12px;">
            <h1 style="font-size:18px;">Thanh toán gần đây</h1>
            <a class="btn link" href="{{ route('payment_center.history') }}">Xem tất cả</a>
        </div>
        @if($recentPayments && $recentPayments->count())
            <table>
                <thead>
                    <tr>
                        <th>Nhân viên</th>
                        <th>Số tiền</th>
                        <th>Trạng thái</th>
                        <th>Ngày TT</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentPayments as $p)
                        <tr>
                            <td>
                                <a href="{{ route('payment_center.payments.show', $p) }}">{{ $p->employee->name ?? '-' }}</a>
                            </td>
                            <td>{{ number_format($p->net, 0) }} VNĐ</td>
                            <td>
                                @if($p->status === 'paid')
                                    <span class="badge">Đã TT</span>
                                @else
                                    <span class="badge pending">Chờ TT</span>
                                @endif
                            </td>
                            <td class="muted">{{ $p->paid_at ? $p->paid_at->format('d/m/Y') : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty">Chưa có thanh toán nào.</div>
        @endif
    </div>

    <div class="card">
        <div class="page-head" style="margin-bottom:12px;">
            <h1 style="font-size:18px;">Lô thanh toán gần đây</h1>
            <a class="btn link" href="{{ route('payment_center.batches.index') }}">Xem tất cả</a>
        </div>
        @if($recentBatches && $recentBatches->count())
            <table>
                <thead>
                    <tr>
                        <th>Mã lô</th>
                        <th>SL</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentBatches as $b)
                        <tr>
                            <td><a href="{{ route('payment_center.batches.show', $b) }}">{{ $b->code }}</a></td>
                            <td>{{ $b->total_items }}</td>
                            <td>{{ number_format($b->total_amount, 0) }} VNĐ</td>
                            <td>
                                @if($b->status === 'pending')
                                    <span class="badge pending">Chờ xử lý</span>
                                @elseif($b->status === 'processing')
                                    <span class="badge" style="background:#dbeafe;color:#1e40af;">Đang xử lý</span>
                                @elseif($b->status === 'completed')
                                    <span class="badge">Hoàn thành</span>
                                @else
                                    <span class="badge" style="background:#fee2e2;color:#dc2626;">Đã hủy</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty">Chưa có lô thanh toán nào.</div>
        @endif
    </div>

</div>

@if($pendingPayments && $pendingPayments->count())
<div class="card" style="margin-top:20px;">
    <div class="page-head" style="margin-bottom:12px;">
        <h1 style="font-size:18px;">Danh sách chờ thanh toán</h1>
        <span class="badge pending">{{ $pendingPayments->count() }} phiếu</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Nhân viên</th>
                <th>Tháng/Năm</th>
                <th>Số tiền thực lĩnh</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pendingPayments as $p)
                <tr>
                    <td>
                        <strong>{{ $p->employee->name ?? '-' }}</strong><br>
                        <small class="muted">{{ $p->employee->employee_code ?? '' }}</small>
                    </td>
                    <td>{{ sprintf('%02d/%04d', $p->month, $p->year) }}</td>
                    <td>{{ number_format($p->net, 0) }} VNĐ</td>
                    <td>
                        <div class="actions">
                            <a class="btn" href="{{ route('payment_center.payments.show', $p) }}">Xem</a>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection
