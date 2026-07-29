@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row g-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Chi tiết chấm công</h4>
                <div class="btn-group">
                    <a href="{{ route('attendance.index') }}" class="btn btn-light">Quay lại</a>
                    <a href="{{ route('attendance.edit', $attendance) }}" class="btn btn-primary">Sửa</a>
                    <form action="{{ route('attendance.destroy', $attendance) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa?');" style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger">Xóa</button>
                    </form>
                    <a href="#" class="btn btn-outline-secondary">In</a>
                    <a href="#" class="btn btn-outline-secondary">Xuất PDF</a>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Thông tin nhân viên</h5>
                    <div class="d-flex gap-3 align-items-center mb-3">
                        <img src="{{ $attendance->employee->avatar ?? '/images/avatars/default.svg' }}" class="rounded-circle" width="72" height="72" alt="avatar">
                        <div>
                            <div class="fw-bold">{{ $attendance->employee->name }}</div>
                            <div class="text-muted">Mã: {{ $attendance->employee->id }}</div>
                            <div class="small text-muted">{{ $attendance->employee->email ?? 'Chưa có' }}</div>
                        </div>
                    </div>

                    <table class="table table-borderless table-sm">
                        <tr><th>Điện thoại</th><td>{{ $attendance->employee->phone ?? 'Chưa có' }}</td></tr>
                        <tr><th>Phòng ban</th><td>{{ $attendance->employee->department->name ?? 'Chưa có' }}</td></tr>
                        <tr><th>Chức vụ</th><td>{{ $attendance->employee->position ?? 'Chưa có' }}</td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-8 col-12">
            <div class="row g-3">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Thông tin chấm công</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless table-sm mb-0">
                                        <tr><th>Mã chấm công</th><td>{{ $attendance->id }}</td></tr>
                                        <tr><th>Ngày</th><td>{{ $attendance->date?->format('d/m/Y') ?? 'Chưa có' }}</td></tr>
                                        <tr><th>Ca</th><td>{{ $attendance->shift ?? 'N/A' }}</td></tr>
                                        <tr><th>Giờ vào</th><td>{{ $attendance->check_in?->format('H:i:s') ?? 'Chưa có' }}</td></tr>
                                        <tr><th>Giờ ra</th><td>{{ $attendance->check_out?->format('H:i:s') ?? 'Chưa có' }}</td></tr>
                                    </table>
                                </div>

                                <div class="col-md-6">
                                    <table class="table table-borderless table-sm mb-0">
                                        <tr><th>Tổng giờ làm</th><td>{{ $workHoursLabel }}</td></tr>
                                        <tr><th>Tăng ca</th><td>{{ $overtimeLabel }}</td></tr>
                                        <tr><th>Đi muộn</th><td>{{ $attendance->late_minutes ?? 0 }} phút</td></tr>
                                        <tr><th>Tiền phạt đi muộn</th><td class="text-danger fw-bold">{{ $attendance->formatted_late_penalty_fee }}</td></tr>
                                        <tr><th>Về sớm</th><td>{{ $attendance->early_leave_minutes ?? 0 }} phút</td></tr>
                                        <tr><th>Trạng thái</th><td>
                                            @php
                                                $status = $attendance->status;
                                                $badge = match($status) {
                                                    'present' => 'success',
                                                    'late' => 'warning',
                                                    'leave_early' => 'info',
                                                    'leave' => 'primary',
                                                    'absent' => 'danger',
                                                    'overtime' => 'secondary',
                                                    default => 'secondary',
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $badge }}">{{ $attendance->status_label }}</span>
                                        </td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Địa điểm chấm công</h5>
                            <table class="table table-borderless table-sm mb-0">
                                <tr><th>Check-in</th><td>{{ $attendance->check_in_location ?? 'Chưa có' }}</td></tr>
                                <tr><th>Check-out</th><td>{{ $attendance->check_out_location ?? 'Chưa có' }}</td></tr>
                                <tr><th>Thiết bị</th><td>{{ $attendance->device ?? 'Chưa có' }}</td></tr>
                                <tr><th>IP</th><td>{{ $attendance->check_in_ip_address ?? $attendance->check_out_ip_address ?? 'Chưa có' }}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Thông tin phê duyệt</h5>
                            <table class="table table-borderless table-sm mb-0">
                                <tr><th>Người duyệt</th><td>{{ $attendance->approver?->name ?? 'Chưa có' }}</td></tr>
                                <tr><th>Thời gian</th><td>{{ $attendance->approved_at?->format('d/m/Y H:i') ?? 'Chưa có' }}</td></tr>
                                <tr><th>Trạng thái duyệt</th><td>{{ $attendance->approved_at ? 'Đã duyệt' : 'Chưa duyệt' }}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Ghi chú</h5>
                            <table class="table table-borderless table-sm mb-0">
                                <tr><th>Lý do đi muộn</th><td>{{ $attendance->check_in_notes ?? 'Chưa có' }}</td></tr>
                                <tr><th>Lý do về sớm</th><td>{{ $attendance->check_out_notes ?? 'Chưa có' }}</td></tr>
                                <tr><th>Ghi chú HR</th><td>{{ $attendance->notes ?? 'Chưa có' }}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
