@extends('layouts.app')

@section('title', 'Nghỉ phép')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('accountant.dashboard') }}">Kế toán</a></li>
<li>Nghỉ phép</li>
@endsection

<style>
    .filter-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
    .filter-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
    .badge.approved { background: #d1fae5; color: #065f46; }
    .badge.rejected { background: #fee2e2; color: var(--danger); }
    .badge.pending { background: #fef3c7; color: #92400e; }
    .actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
</style>

<div class="page-head">
    <div>
        <h1>Nghỉ phép</h1>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('accountant.dashboard') }}">Quay lại</a>
    </div>
</div>

<div class="filter-card">
    <form method="GET" class="filter-row" style="gap: 15px; align-items: flex-end;">
        <div class="field">
            <label>Trạng thái đơn</label>
            <select name="status">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
            </select>
        </div>
        <div class="field">
            <label>Loại phép nghỉ</label>
            <select name="type">
                <option value="">-- Tất cả loại phép --</option>
                @foreach(\App\Support\LeaveTypes::all() as $value => $meta)
                    <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>{{ $meta['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex; gap:10px;">
            <button type="submit" class="btn primary">Lọc dữ liệu</button>
            <a href="{{ route('accountant.leave_requests') }}" class="btn">Đặt lại</a>
        </div>
    </form>
</div>

@if(isset($leaveRequests) && $leaveRequests->count())
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Nhân viên</th>
                    <th>Loại phép</th>
                    <th>Từ ngày</th>
                    <th>Đến ngày</th>
                    <th>Số ngày</th>
                    <th>Lý do</th>
                    <th>Khẩn cấp</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @foreach($leaveRequests as $leave)
                    <tr>
                        <td><strong>{{ optional($leave->employee)->name }}</strong></td>
                        <td>{{ $leave->type_label }}</td>
                        <td>{{ \Carbon\Carbon::parse($leave->start_date)->format('d/m/Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($leave->end_date)->format('d/m/Y') }}</td>
                        <td>
                            @if($leave->half_day)
                                <span style="color: #1976d2; font-weight: bold;">{{ $leave->days }} (1/2 ngày)</span>
                            @else
                                {{ $leave->days }}
                            @endif
                        </td>
                        <td>{{ $leave->reason ?? 'Không có lý do' }}</td>
                        <td>
                            @if($leave->is_urgent)
                                <span style="color: #d32f2f; font-weight: bold; background: #ffebee; padding: 2px 8px; border-radius: 4px;">Khẩn cấp</span>
                                @if($leave->urgent_reason)
                                    <div style="margin-top:4px;font-size:12px;color:#666;max-width:200px;">
                                        {{ Str::limit($leave->urgent_reason, 80) }}
                                    </div>
                                @endif
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <span class="badge @if($leave->status === 'approved') approved @elseif($leave->status === 'rejected') rejected @else pending @endif">
                                @if($leave->status === 'approved') ✓ Đã duyệt
                                @elseif($leave->status === 'rejected') ✗ Từ chối
                                @elseif($leave->status === 'cancelled') Đã hủy
                                @else ⏳ Chờ duyệt
                                @endif
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="card">
        <div class="empty">Không có đơn nghỉ phép nào.</div>
    </div>
@endif

@endsection
