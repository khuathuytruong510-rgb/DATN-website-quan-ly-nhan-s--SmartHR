@extends('layouts.app')

@section('title', 'Lịch sử thanh toán')

@section('content')

@section('breadcrumb')
<li><a href="{{ route('payment_center.dashboard') }}">Trung tâm thanh toán</a></li>
<li>Lịch sử thanh toán</li>
@endsection

<div class="page-head">
    <div>
        <h1>Lịch sử thanh toán</h1>
        <p class="muted">Theo dõi lịch sử thanh toán lương nhân viên</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('payment_center.export') }}">Xuất báo cáo</a>
    </div>
</div>

<div class="card" style="margin-bottom:20px;">
    <form method="GET" action="{{ route('payment_center.history') }}" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
        <div class="field" style="margin-bottom:0; min-width:130px;">
            <label>Tháng</label>
            <select name="month">
                <option value="">Tất cả</option>
                @for($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>Tháng {{ $i }}</option>
                @endfor
            </select>
        </div>
        <div class="field" style="margin-bottom:0; min-width:130px;">
            <label>Năm</label>
            <select name="year">
                <option value="">Tất cả</option>
                @for($y = now()->year - 3; $y <= now()->year + 1; $y++)
                    <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </div>
        <div class="field" style="margin-bottom:0; min-width:140px;">
            <label>Trạng thái</label>
            <select name="status">
                <option value="">Tất cả</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ thanh toán</option>
                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
            </select>
        </div>
        <div class="field" style="margin-bottom:0; flex:1; min-width:180px;">
            <label>Tìm nhân viên</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tên, mã nhân viên...">
        </div>
        <div class="field" style="margin-bottom:0; min-width:160px;">
            <label>Phòng ban</label>
            <select name="department_id">
                <option value="">Tất cả</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn primary" type="submit">Lọc</button>
        <a class="btn" href="{{ route('payment_center.history') }}">Đặt lại</a>
    </form>
</div>

<div class="card">
    @if($payments && $payments->count())
        <table>
            <thead>
                <tr>
                    <th>Nhân viên</th>
                    <th>Mã NV</th>
                    <th>Tháng/Năm</th>
                    <th>Tổng lương</th>
                    <th>Thực lĩnh</th>
                    <th>Trạng thái</th>
                    <th>Hình thức</th>
                    <th>Ngày TT</th>
                    <th>Lô thanh toán</th>
                    <th>Đối soát</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $p)
                    <tr>
                        <td>
                            <a href="{{ route('payment_center.payments.show', $p) }}"><strong>{{ $p->employee->name ?? '-' }}</strong></a>
                        </td>
                        <td class="muted">{{ $p->employee->employee_code ?? '-' }}</td>
                        <td>{{ sprintf('%02d/%04d', $p->month, $p->year) }}</td>
                        <td>{{ number_format($p->total, 0) }} VNĐ</td>
                        <td><strong>{{ number_format($p->net, 0) }} VNĐ</strong></td>
                        <td>
                            @if($p->status === 'paid')
                                <span class="badge">Đã TT</span>
                            @else
                                <span class="badge pending">Chờ TT</span>
                            @endif
                        </td>
                        <td>
                            @if($p->payment_method === 'bank_transfer')
                                <span class="badge" style="background:#dbeafe;color:#1e40af;">Chuyển khoản</span>
                            @elseif($p->payment_method === 'cash')
                                <span class="badge" style="background:#fef3c7;color:#92400e;">Tiền mặt</span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td class="muted">{{ $p->paid_at ? $p->paid_at->format('d/m/Y') : '—' }}</td>
                        <td>
                            @if($p->batch)
                                <a href="{{ route('payment_center.batches.show', $p->batch) }}">{{ $p->batch->code }}</a>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($p->reconciliation_status === 'reconciled')
                                <span class="badge">Đã đối soát</span>
                            @elseif($p->reconciliation_status === 'discrepancy')
                                <span class="badge" style="background:#fee2e2;color:#dc2626;">Chênh lệch</span>
                            @else
                                <span class="muted">Chưa</span>
                            @endif
                        </td>
                        <td>
                            <a class="btn" href="{{ route('payment_center.payments.show', $p) }}">Xem</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="pagination">{{ $payments->links() }}</div>
    @else
        <div class="empty">Không tìm thấy thanh toán nào.</div>
    @endif
</div>

@endsection
