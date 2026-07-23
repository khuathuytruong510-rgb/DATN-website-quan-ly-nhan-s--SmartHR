@extends('layouts.app')

@section('title', 'Danh sách lô thanh toán')

@section('content')

@section('breadcrumb')
<li><a href="{{ route('payment_center.dashboard') }}">Trung tâm thanh toán</a></li>
<li>Lô thanh toán</li>
@endsection

<div class="page-head">
    <div>
        <h1>Lô thanh toán</h1>
        <p class="muted">Quản lý các lô thanh toán lương</p>
    </div>
    <div class="actions">
        <a class="btn primary" href="{{ route('payment_center.batches.create') }}">Tạo lô mới</a>
    </div>
</div>

<div class="card" style="margin-bottom:20px;">
    <form method="GET" action="{{ route('payment_center.batches.index') }}" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
        <div class="field" style="margin-bottom:0; min-width:160px;">
            <label>Trạng thái</label>
            <select name="status">
                <option value="">Tất cả</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Đang xử lý</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
            </select>
        </div>
        <button class="btn primary" type="submit">Lọc</button>
        <a class="btn" href="{{ route('payment_center.batches.index') }}">Đặt lại</a>
    </form>
</div>

<div class="card">
    @if($batches && $batches->count())
        <table>
            <thead>
                <tr>
                    <th>Mã lô</th>
                    <th>Tên lô</th>
                    <th>Tháng/Năm</th>
                    <th>Số phiếu</th>
                    <th>Tổng tiền</th>
                    <th>Đã thanh toán</th>
                    <th>Trạng thái</th>
                    <th>Người tạo</th>
                    <th>Ngày tạo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($batches as $b)
                    <tr>
                        <td><a href="{{ route('payment_center.batches.show', $b) }}"><strong>{{ $b->code }}</strong></a></td>
                        <td>{{ $b->name }}</td>
                        <td>{{ sprintf('%02d/%04d', $b->month, $b->year) }}</td>
                        <td>{{ $b->total_items }}</td>
                        <td>{{ number_format($b->total_amount, 0) }} VNĐ</td>
                        <td>{{ number_format($b->total_paid, 0) }} VNĐ</td>
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
                        <td>{{ $b->createdBy->name ?? '-' }}</td>
                        <td class="muted">{{ $b->created_at->format('d/m/Y') }}</td>
                        <td>
                            <a class="btn" href="{{ route('payment_center.batches.show', $b) }}">Xem</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="pagination">{{ $batches->links() }}</div>
    @else
        <div class="empty">Chưa có lô thanh toán nào. Hãy tạo lô mới.</div>
    @endif
</div>

@endsection
