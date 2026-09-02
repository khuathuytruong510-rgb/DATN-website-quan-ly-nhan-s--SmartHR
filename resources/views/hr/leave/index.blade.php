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
        <p class="muted">Nhân viên gửi đơn → HR duyệt. Đơn của chính HR → Giám đốc duyệt. Đơn đã duyệt được dùng khi tính lương.</p>
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
                @foreach(\App\Support\LeaveTypes::all() as $value => $meta)
                    <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>{{ $meta['label'] }}</option>
                @endforeach
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

@php $currentUser = Auth::user(); @endphp

@if(!empty($overtimeRequests) && $overtimeRequests->isNotEmpty())
<div class="card" style="margin-bottom:20px;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <h2 style="margin:0;">Đăng ký tăng ca chờ duyệt</h2>
        <a class="btn" href="{{ route('overtime_requests.index') }}">Quản lý tăng ca</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>Nhân viên</th>
                <th>Ngày</th>
                <th>Giờ</th>
                <th>Lý do</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($overtimeRequests as $ot)
                <tr>
                    <td>{{ optional($ot->employee)->name }}</td>
                    <td>{{ optional($ot->date)->format('d/m/Y') }}</td>
                    <td>{{ $ot->start_time }} – {{ $ot->end_time }}</td>
                    <td>{{ $ot->reason ?: '—' }}</td>
                    <td>
                        @if(\App\Support\RequestApprover::canReview($currentUser, $ot->employee))
                            <form method="POST" action="{{ route('overtime_requests.approve', $ot) }}" style="display:inline">
                                @csrf
                                <button class="btn" type="submit">Duyệt</button>
                            </form>
                            <form method="POST" action="{{ route('overtime_requests.reject', $ot) }}" style="display:inline" onsubmit="return submitRejectReason(this)">
                                @csrf
                                <input type="hidden" name="rejection_reason" value="">
                                <button class="btn" type="submit">Từ chối</button>
                            </form>
                        @elseif(\App\Support\RequestApprover::needsDirector($ot->employee))
                            <span class="muted">Chờ Giám đốc duyệt</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if($leaveRequests->count())
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
                                @if($leave->status === 'pending' && \App\Support\RequestApprover::canReview($currentUser, $leave->employee))
                                    <form method="POST" action="{{ route('leave_requests.approve', $leave) }}" style="display:inline">
                                        @csrf
                                        <button class="btn" type="submit">Duyệt</button>
                                    </form>
                                    <form method="POST" action="{{ route('leave_requests.reject', $leave) }}" style="display:inline" onsubmit="return submitRejectReason(this)">
                                        @csrf
                                        <input type="hidden" name="rejection_reason" value="">
                                        <button class="btn" type="submit">Từ chối</button>
                                    </form>
                                @elseif($leave->status === 'pending' && \App\Support\RequestApprover::needsDirector($leave->employee))
                                    <span class="muted">Chờ Giám đốc duyệt</span>
                                @endif
                                @if($currentUser->is_hr && $leave->status === 'pending')
                                    <form method="POST" action="{{ route('leave_requests.destroy', $leave) }}" style="display:inline" data-confirm="Xóa đơn nghỉ phép đang chờ duyệt?">
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

<div class="modal fade" id="leaveRejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Lý do từ chối</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <textarea id="leaveRejectInput" class="form-control" rows="3" required placeholder="Nhập lý do từ chối đơn nghỉ phép..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" id="leaveRejectOk">Xác nhận từ chối</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var rejectForm = null;
        var rejectModal = null;

        document.addEventListener('DOMContentLoaded', function () {
            var modalEl = document.getElementById('leaveRejectModal');
            var input = document.getElementById('leaveRejectInput');
            if (!modalEl || !input) return;

            rejectModal = new bootstrap.Modal(modalEl);

            document.getElementById('leaveRejectOk').addEventListener('click', function () {
                var reason = input.value.trim();
                if (!reason) {
                    alert('Bạn phải nhập lý do từ chối.');
                    return;
                }
                if (rejectForm) {
                    rejectForm.querySelector('input[name="rejection_reason"]').value = reason;
                    rejectForm.removeAttribute('onsubmit');
                    rejectForm.submit();
                }
                rejectModal.hide();
                rejectForm = null;
                input.value = '';
            });

            modalEl.addEventListener('hidden.bs.modal', function () {
                rejectForm = null;
                input.value = '';
            });
        });

        window.submitRejectReason = function (form) {
            rejectForm = form;
            if (rejectModal) {
                rejectModal.show();
            } else {
                var reason = prompt('Vui lòng nhập lý do từ chối:');
                if (!reason || !reason.trim()) {
                    alert('Bạn phải nhập lý do từ chối.');
                    return false;
                }
                form.querySelector('input[name="rejection_reason"]').value = reason.trim();
                return true;
            }
            return false;
        };
    })();
</script>

@endsection
