@extends('layouts.app')

@push('styles')
<style>
    .filter-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
    .filter-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
    .filter-row .field { margin-bottom: 0; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <h1>Quản Lý Đơn Nghỉ Phép</h1>
        <p class="muted">Quản lý trạng thái và thao tác với đơn nghỉ phép.</p>
    </div>
</div>

<div class="filter-card">
    <form method="GET" class="filter-row" style="gap: 15px; align-items: flex-end;">
        <div class="field">
            <label>Trạng Lý Đơn Nghỉ Phép</label>
            <select name="status">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Từ chối</option>
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
            <button type="submit" class="btn primary"><i class="bi bi-search me-1"></i> Lọc Dữ Liệu</button>
            <a href="{{ route('leave_requests.index') }}" class="btn">Đặt lại</a>
        </div>
    </form>
</div>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div style="flex: 1;"></div>
    <a href="{{ route('leave_requests.create') }}" class="btn primary"><i class="bi bi-plus-lg me-1"></i> Tạo Đơn Xin Nghỉ</a>
</div>

@if($leaveRequests->count())
    @php $currentUser = Auth::user(); @endphp
    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
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
                                    <span class="text-primary fw-bold">{{ $leave->days }} (1/2 ngày)</span>
                                @else
                                    {{ $leave->days }}
                                @endif
                            </td>
                            <td>{{ $leave->reason ?? 'Không có lý do' }}</td>
                            <td>
                                @if($leave->is_urgent)
                                    <span class="badge bg-danger">Khẩn cấp</span>
                                    @if($leave->urgent_reason)
                                        <div class="text-muted mt-1" style="font-size:12px;max-width:200px;">
                                            {{ Str::limit($leave->urgent_reason, 80) }}
                                        </div>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <span class="badge 
                                    @if($leave->status === 'approved') bg-success
                                    @elseif($leave->status === 'rejected') bg-danger
                                    @else bg-warning text-dark
                                    @endif
                                ">
                                    @if($leave->status === 'approved') <i class="bi bi-check-circle"></i> Đã duyệt
                                    @elseif($leave->status === 'rejected') <i class="bi bi-x-circle"></i> Từ chối
                                    @else <i class="bi bi-clock"></i> Chờ duyệt
                                    @endif
                                </span>
                                @if($leave->approved_at)
                                    <div class="text-muted mt-1" style="font-size:12px;">
                                        Người duyệt: {{ optional($leave->approver)->name ?? 'N/A' }} • {{ \Carbon\Carbon::parse($leave->approved_at)->format('d/m/Y H:i') }}
                                    </div>
                                @endif
                                @if($leave->status === 'rejected' && $leave->rejection_reason)
                                    <div class="text-danger mt-1" style="font-size:12px;">
                                        <i class="bi bi-info-circle"></i> Lý do từ chối: {{ $leave->rejection_reason }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="actions">
                                    @if($leave->status === 'pending')
                                        <form method="POST" action="{{ route('leave_requests.approve', $leave) }}" style="display:inline">
                                            @csrf
                                            <button class="btn btn-sm btn-success" type="submit">
                                                <i class="bi bi-check-lg"></i> {{ $currentUser->is_admin ? 'Duyệt' : 'Xác nhận' }}
                                            </button>
                                        </form>
                                        <button class="btn btn-sm btn-danger" type="button" onclick="openRejectModal({{ $leave->id }})">
                                            <i class="bi bi-x-lg"></i> Từ chối
                                        </button>
                                    @endif
                                    @if($currentUser->is_admin)
                                        <form method="POST" action="{{ route('leave_requests.destroy', $leave) }}" style="display:inline" onsubmit="return confirm('Xóa đơn nghỉ phép này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="pagination">
        {{ $leaveRequests->links() }}
    </div>
@else
    <div class="card">
        <div class="empty text-center py-4">
            <i class="bi bi-inbox" style="font-size:48px;color:#cbd5e1;"></i>
            <p class="mt-2">Không có đơn nghỉ phép nào</p>
        </div>
    </div>
@endif

<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectModalLabel">
                    <i class="bi bi-x-circle text-danger me-2"></i>Từ chối đơn nghỉ phép
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-muted mb-3">Vui lòng nhập lý do từ chối đơn nghỉ phép:</p>
                    <div class="field mb-0">
                        <label for="rejection_reason">Lý do từ chối <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" id="rejection_reason" rows="4" required placeholder="Nhập lý do từ chối..." class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-lg me-1"></i> Xác nhận từ chối
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openRejectModal(leaveId) {
    var form = document.getElementById('rejectForm');
    form.action = '/hr/leave-requests/' + leaveId + '/reject';
    var textarea = document.getElementById('rejection_reason');
    textarea.value = '';
    var modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
}

document.getElementById('rejectForm').addEventListener('submit', function(e) {
    var reason = document.getElementById('rejection_reason').value.trim();
    if (!reason) {
        e.preventDefault();
        document.getElementById('rejection_reason').classList.add('is-invalid');
        return false;
    }
    document.getElementById('rejection_reason').classList.remove('is-invalid');
});
</script>
@endpush
