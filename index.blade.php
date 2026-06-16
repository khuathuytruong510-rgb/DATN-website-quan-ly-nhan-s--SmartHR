@extends('layouts.app') 

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-secondary">📋 Quản Lý Đơn Nghỉ Phép</h2>
        <a href="{{ route('leave_requests.create') }}" class="btn btn-primary fw-bold">➕ Tạo Đơn Xin Nghỉ</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body bg-light rounded">
            <form action="{{ route('leave_requests.index') }}" method="GET" class="row g-3">
                <div class="col-md-8">
                    <label class="form-label small fw-bold">Trạng thái đơn</label>
                    <select name="status" class="form-select">
                        <option value="">-- Tất cả trạng thái --</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>⏳ Chờ duyệt</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>✅ Đã duyệt</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>❌ Từ chối</option>
                    </select>
                </div>
                <div class="col-md-4">
    <label>Loại phép</label>

    <select name="type" class="form-select">

        <option value="">Tất cả</option>

        <option value="annual">Nghỉ năm</option>

        <option value="sick">Nghỉ ốm</option>

        <option value="personal">Nghỉ cá nhân</option>

        <option value="unpaid">Nghỉ không lương</option>

    </select>

</div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-secondary w-100 fw-bold">🔍 Lọc Dữ Liệu</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover table-striped mb-0 alignment-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">Nhân viên</th>
                        <th>Loại phép</th>
                        <th>Từ ngày</th>
                        <th>Đến ngày</th>
                        <th class="text-center">Số ngày</th>
                        <th>Lý do</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="text-end pe-3">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaveRequests as $leave)
                    <tr>
                        <td class="fw-bold ps-3">{{ $leave->employee->name ?? 'N/A' }}</td>
                        <td>
                            @switch($leave->type)
                                @case('annual')
                                    Nghỉ phép năm
                                    @break

                                @case('sick')
                                    Nghỉ ốm
                                    @break

                                @case('personal')
                                    Nghỉ cá nhân
                                    @break

                                @case('unpaid')
                                    Nghỉ không lương
                                    @break

                                @default
                                    {{ $leave->type }}

                            @endswitch
                        </td>
                        <td>{{ date('d/m/Y', strtotime($leave->start_date)) }}</td>
                        <td>{{ date('d/m/Y', strtotime($leave->end_date)) }}</td>
                        <td class="text-center fw-bold text-primary">{{ $leave->days }}</td>
                        <td><small class="text-muted">{{ $leave->reason }}</small></td>
                        <td class="text-center">
                            @if($leave->status === 'pending')
                                <span class="badge bg-warning text-dark px-2 py-1.5">⏳ Chờ duyệt</span>
                            @elseif($leave->status === 'approved')
                                <span class="badge bg-success px-2 py-1.5 text-white">✅ Đã duyệt</span>
                            @else
                                <span class="badge bg-danger px-2 py-1.5 text-white">❌ Từ chối</span>
                            @endif
                        </td>
                        <td class="text-end pe-3">

                            @if($leave->status === 'pending')
                                <div class="d-inline-flex gap-1">

                                    <form action="{{ route('leave.approve', $leave->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="status" value="approved">
                                        <button class="btn btn-success btn-sm">Duyệt</button>
                                    </form>

                                    <form action="{{ route('leave.approve', $leave->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="status" value="rejected">
                                        <button class="btn btn-danger btn-sm">Từ chối</button>
                                    </form>

                                    <form action="{{ route('leave_requests.destroy', $leave->id) }}"
                                        method="POST"
                                        onsubmit="return confirm('Bạn có chắc muốn xóa?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-secondary btn-sm">🗑 Xóa</button>
                                    </form>

                                </div>
                            @else

                                <form action="{{ route('leave_requests.destroy', $leave->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Bạn có chắc muốn xóa?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-secondary btn-sm">🗑 Xóa</button>
                                </form>

                            @endif

                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">Chưa có đơn xin nghỉ phép nào khớp với bộ lọc.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="mt-3">
        {{ $leaveRequests->links() }}
    </div>
</div>
@endsection