@extends('layouts.app')

@section('title', 'Nghỉ phép')

@section('content')
@php $currentUser = auth()->user(); @endphp

<style>
    .leave-index .badge.approved { background: #d1fae5; color: #065f46; }
    .leave-index .badge.rejected { background: #fee2e2; color: var(--danger); }
    .leave-index .badge.pending { background: #fef3c7; color: #92400e; }
    .leave-index .badge.cancelled { background: #e2e8f0; color: #475569; }
    .leave-index .table-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
    .leave-index .reason-cell { max-width: 220px; line-height: 1.4; }
    .leave-index .meta-line { margin-top: 6px; font-size: 12px; color: var(--muted); }
    .leave-index .urgent-tag {
        display: inline-block; color: #b91c1c; font-weight: 700;
        background: #fee2e2; padding: 2px 8px; border-radius: 4px; font-size: 12px;
    }
</style>

<div class="page-head leave-index">
    <div>
        <h1>Nghỉ phép</h1>
    </div>
    @if($currentUser?->is_hr)
    <div class="page-actions">
        <a href="{{ route('leave_requests.create') }}" class="btn primary">Tạo đơn xin nghỉ</a>
    </div>
    @endif
</div>

<div class="card filter-card">
    <form method="GET" action="{{ route('leave_requests.index') }}" class="filter-form">
        <div class="field-group">
            <label class="form-label" for="leaveStatus">Trạng thái</label>
            <select id="leaveStatus" class="form-select" name="status">
                <option value="">Tất cả trạng thái</option>
                <option value="pending" @selected(request('status') === 'pending')>Chờ duyệt</option>
                <option value="approved" @selected(request('status') === 'approved')>Đã duyệt</option>
                <option value="rejected" @selected(request('status') === 'rejected')>Từ chối</option>
                <option value="cancelled" @selected(request('status') === 'cancelled')>Đã hủy</option>
            </select>
        </div>
        <div class="field-group">
            <label class="form-label" for="leaveType">Loại phép</label>
            <select id="leaveType" class="form-select" name="type">
                <option value="">Tất cả loại phép</option>
                @foreach(\App\Support\LeaveTypes::all() as $value => $meta)
                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $meta['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="field-group actions-row">
            <button type="submit" class="btn primary">Lọc</button>
            <a href="{{ route('leave_requests.index') }}" class="btn">Xóa lọc</a>
        </div>
    </form>
</div>

@if($leaveRequests->count())
    <div class="card leave-index">
        <div class="table-responsive">
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
                        <th>Thao tác</th>
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
                                    {{ $leave->days }} <span class="muted">(nửa ngày)</span>
                                @else
                                    {{ $leave->days }}
                                @endif
                            </td>
                            <td class="reason-cell">{{ $leave->reason ? \Illuminate\Support\Str::limit($leave->reason, 80) : '—' }}</td>
                            <td>
                                @if($leave->is_urgent)
                                    <span class="urgent-tag">Khẩn cấp</span>
                                    @if($leave->urgent_reason)
                                        <div class="meta-line">{{ \Illuminate\Support\Str::limit($leave->urgent_reason, 60) }}</div>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <span @class([
                                    'badge',
                                    'approved' => $leave->status === 'approved',
                                    'rejected' => $leave->status === 'rejected',
                                    'cancelled' => $leave->status === 'cancelled',
                                    'pending' => ! in_array($leave->status, ['approved', 'rejected', 'cancelled'], true),
                                ])>
                                    @switch($leave->status)
                                        @case('approved') Đã duyệt @break
                                        @case('rejected') Từ chối @break
                                        @case('cancelled') Đã hủy @break
                                        @default Chờ duyệt
                                    @endswitch
                                </span>
                                @if($leave->approved_at)
                                    <div class="meta-line">
                                        {{ optional($leave->approver)->name ?? '—' }} · {{ \Carbon\Carbon::parse($leave->approved_at)->format('d/m/Y H:i') }}
                                    </div>
                                @endif
                                @if($leave->status === 'rejected' && $leave->rejection_reason)
                                    <div class="meta-line">{{ \Illuminate\Support\Str::limit($leave->rejection_reason, 80) }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="table-actions">
                                    @if($leave->status === 'pending' && \App\Support\RequestApprover::canReview($currentUser, $leave->employee))
                                        <form method="POST" action="{{ route('leave_requests.approve', $leave) }}" style="display:inline">
                                            @csrf
                                            <button class="btn btn-sm" type="submit">Duyệt</button>
                                        </form>
                                        <form method="POST" action="{{ route('leave_requests.reject', $leave) }}" style="display:inline" onsubmit="return submitRejectReason(this)">
                                            @csrf
                                            <input type="hidden" name="rejection_reason" value="">
                                            <button class="btn btn-sm" type="submit">Từ chối</button>
                                        </form>
                                    @elseif($leave->status === 'pending' && \App\Support\RequestApprover::needsDirector($leave->employee))
                                        <span class="muted">Chờ Giám đốc duyệt</span>
                                    @endif
                                    @if($currentUser?->is_hr && $leave->status === 'pending')
                                        <form method="POST" action="{{ route('leave_requests.destroy', $leave) }}" style="display:inline" data-confirm="Xóa đơn nghỉ phép đang chờ duyệt?" data-confirm-variant="danger">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm danger" type="submit">Xóa</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($leaveRequests->hasPages())
            <div class="pagination" style="margin-top:16px;">
                {{ $leaveRequests->links() }}
            </div>
        @endif
    </div>
@else
    <div class="card leave-index">
        <div class="empty" style="padding:28px 12px;">Không có đơn nghỉ phép phù hợp bộ lọc.</div>
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
