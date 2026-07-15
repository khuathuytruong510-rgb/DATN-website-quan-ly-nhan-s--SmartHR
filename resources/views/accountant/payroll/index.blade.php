@extends('layouts.app')

@section('title', 'Quản lý bảng lương')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('accountant.dashboard') }}">Kế toán</a></li>
<li>Quản lý bảng lương</li>
@endsection

<div class="page-head">
    <div>
        <h1>Quản lý bảng lương</h1>
        <p class="muted">Danh sách bảng lương</p>
    </div>
    <div class="actions">
        <a class="btn primary" href="{{ route('accountant.payroll.generate') }}">Tính lương</a>
    </div>
</div>

<div class="card">
    <form method="GET" class="row" style="display:flex; gap:12px; align-items:center; margin-bottom:12px;">
        <input name="q" placeholder="Tìm theo tên/ email/ tháng" value="{{ request('q') }}">
        <select name="status">
            <option value="">Tất cả trạng thái</option>
            <option value="pending" {{ request('status')=='pending' ? 'selected' : '' }}>Chờ duyệt</option>
            <option value="approved" {{ request('status')=='approved' ? 'selected' : '' }}>Đã duyệt</option>
            <option value="paid" {{ request('status')=='paid' ? 'selected' : '' }}>Đã trả</option>
        </select>
        <button class="btn" type="submit">Tìm</button>
    </form>

    @if($payrolls->count() === 0)
        <div class="empty">Chưa có bảng lương. Hãy tạo bảng lương mới.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Tháng</th>
                    <th>Nhân viên</th>
                    <th>Tổng</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($payrolls as $p)
                    <tr>
                        <td>{{ $p->month }}</td>
                        <td>{{ optional($p->employee)->name }}<br><small class="muted">{{ optional($p->employee)->email }}</small></td>
                        <td>{{ number_format($p->total_salary ?? 0,0, '.', ',') }} VNĐ</td>
                        <td>
                            @if($p->status === 'pending')<span class="badge pending">Chờ duyệt</span>
                            @elseif($p->status === 'approved')<span class="badge">Đã duyệt</span>
                            @elseif($p->status === 'paid')<span class="badge bg-success">Đã trả</span>
                            @endif
                        </td>
                        <td style="text-align:right; display:flex; gap:8px; justify-content:flex-end;">
                            <a class="btn" href="{{ route('accountant.payroll.show', $p) }}">Xem</a>
                            <form method="POST" action="{{ route('accountant.payroll.recalculate', $p) }}" style="display:inline;">
                                @csrf
                                <button class="btn" type="submit">Tính lại</button>
                            </form>
                            @if(!$p->locked)
                                <form method="POST" action="{{ route('accountant.payroll.lock', $p) }}" style="display:inline;">
                                    @csrf
                                    <button class="btn" type="submit">Khoá</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('accountant.payroll.unlock', $p) }}" style="display:inline;">
                                    @csrf
                                    <button class="btn" type="submit">Mở khoá</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="pagination">{{ $payrolls->links() }}</div>
    @endif
</div>

@endsection
