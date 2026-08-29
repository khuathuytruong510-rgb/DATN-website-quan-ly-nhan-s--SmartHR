@extends('layouts.app')

@section('content')
<style>
    .filter-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
    .filter-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
    .filter-row .field { margin-bottom: 0; }
    .btn-create { background: var(--primary); color: #fff; margin-bottom: 20px; }
    .badge.approved { background: #d1fae5; color: #065f46; }
    .badge.rejected { background: #fee2e2; color: var(--danger); }
    .badge.pending { background: #fef3c7; color: #92400e; }
    .actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
</style>

<div class="page-head">
    <div>
        <h1>Quản Lý Đơn Nghỉ Phép</h1>
        <p class="muted">Nhân viên gửi đơn → HR duyệt/từ chối. Đơn đã duyệt được kế toán dùng làm dữ liệu đầu vào khi tính lương. Giám đốc không duyệt từng đơn nghỉ phép.</p>
    </div>
</div>

<div class="filter-card">
    <form method="GET" class="filter-row" style="gap: 15px; align-items: flex-end;">
        <div class="field">
            <label>Trạng thái</label>
            <select name="status">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
            </select>
        </div>
        
        <div class="field">
            <label>Loại Phép Nghỉ</label>
            <select name="type">
                <option value="">-- Tất cả loại phép --</option>
                <option value="annual" {{ request('type') === 'annual' ? 'selected' : '' }}>Nghỉ hàng năm</option>
                <option value="sick" {{ request('type') === 'sick' ? 'selected' : '' }}>Nghỉ ốm đau</option>
                <option value="unpaid" {{ request('type') === 'unpaid' ? 'selected' : '' }}>Nghỉ không lương</option>
                <option value="maternity" {{ request('type') === 'maternity' ? 'selected' : '' }}>Nghỉ sinh con</option>
            </select>
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn primary">🔍 Lọc Dữ Liệu</button>
            <a href="{{ route('leave_requests.index') }}" class="btn">Đặt lại</a>
        </div>
    </form>
</div>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div style="flex: 1;"></div>
    @if(auth()->user()?->is_hr)
    <a href="{{ route('leave_requests.create') }}" class="btn primary btn-create">+ Tạo Đơn Xin Nghỉ</a>
    @endif
</div>

@if($leaveRequests->count())
    @php $currentUser = Auth::user(); @endphp
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>NHÂN VIÊN</th>
                    <th>LOẠI PHÉP</th>
                    <th>TỪ NGÀY</th>
                    <th>ĐẾN NGÀY</th>
                    <th>SỐ NGÀY</th>
                    <th>LÝ DO</th>
                    <th>KHẨN CẤP</th>
                    <th>TRẠNG THÁI</th>
                    <th>HÀNH ĐỘNG</th>
                </tr>
            </thead>
            <tbody>
                @foreach($leaveRequests as $leave)
                    <tr>
                        <td><strong>{{ optional($leave->employee)->name }}</strong></td>
                        <td>{{ ucfirst(str_replace('_', ' ', $leave->type ?? 'N/A')) }}</td>
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
                            <span class="badge 
                                @if($leave->status === 'approved') approved
                                @elseif($leave->status === 'rejected') rejected
                                @else pending
                                @endif
                            ">
                                @if($leave->status === 'approved') ✓ Đã duyệt
                                @elseif($leave->status === 'rejected') ✗ Từ chối
                                @elseif($leave->status === 'cancelled') Đã hủy
                                @else ⏳ Chờ duyệt
                                @endif
                            </span>
                            @if($leave->approved_at)
                                <div style="margin-top:6px;font-size:12px;color:var(--muted);">
                                    Người duyệt: {{ optional($leave->approver)->name ?? 'N/A' }} • {{ \Carbon\Carbon::parse($leave->approved_at)->format('d/m/Y H:i') }}
                                </div>
                            @endif
                            @if($leave->status === 'rejected' && $leave->rejection_reason)
                                <div style="margin-top:6px;font-size:12px;color:var(--muted);">
                                    Lý do từ chối: {{ $leave->rejection_reason }}
                                </div>
                            @endif
                        </td>
                        <td>
                            <div class="actions">
                                @if($leave->status === 'pending' && $currentUser->is_hr)
                                    <form method="POST" action="{{ route('leave_requests.approve', $leave) }}" style="display:inline">
                                        @csrf
                                        <button class="btn" type="submit">Duyệt</button>
                                    </form>
                                    <form method="POST" action="{{ route('leave_requests.reject', $leave) }}" style="display:inline" onsubmit="return submitRejectReason(this)">
                                        @csrf
                                        <input type="hidden" name="rejection_reason" value="">
                                        <button class="btn" type="submit">Từ chối</button>
                                    </form>
                                @endif
                                @if($currentUser->is_hr && $leave->status === 'pending')
                                    <form method="POST" action="{{ route('leave_requests.destroy', $leave) }}" style="display:inline" onsubmit="return confirm('Xóa đơn nghỉ phép đang chờ duyệt?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn danger" type="submit">Xóa</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $leaveRequests->links() }}
    </div>
@else
    <div class="empty">
        📋 Không có đơn nghỉ phép nào
    </div>
@endif

<script>
    function submitRejectReason(form) {
        var reason = prompt('Vui lòng nhập lý do từ chối:');

        if (!reason || !reason.trim()) {
            alert('Bạn phải nhập lý do từ chối.');
            return false;
        }

        form.querySelector('input[name="rejection_reason"]').value = reason.trim();
        return true;
    }
</script>

@endsection
