@extends('layouts.app')

@section('content')
@php
    $directorView = auth()->user()?->is_director && ! auth()->user()?->canManageHr();
    $contractStatusLabel = function (?string $status): string {
        return match ($status) {
            'waiting_employee_signature', 'waiting_employee' => 'Chờ NV ký',
            'waiting_director_signature', 'waiting_director', 'pending_signature' => 'Chờ GĐ ký',
            'director_signed' => 'Chờ NV ký',
            'employee_signed', 'signed' => 'Đã ký',
            'active' => 'Có hiệu lực',
            'expired' => 'Hết hạn',
            'rejected' => 'Từ chối',
            'cancelled', 'terminated' => 'Đã chấm dứt',
            'draft' => 'Nháp',
            default => $status ? ucfirst($status) : 'Chưa có HĐ',
        };
    };
@endphp
<div class="page-head">
    <div>
        <h1>Nhân viên</h1>
        <p class="muted">{{ $directorView ? 'Xem thông tin phục vụ quản lý và phê duyệt. Không chỉnh sửa hồ sơ.' : 'Quản lý thông tin nhân viên và vị trí.' }}</p>
    </div>
    @if(auth()->user()?->canManageHr())
    <div class="page-actions">
        <a class="btn" href="{{ route('transfers.create') }}">Điều chuyển nhân viên</a>
        <a class="btn primary" href="{{ route('employees.create') }}">Tạo nhân viên</a>
    </div>
    @endif
</div>

@php $filters = $filters ?? []; @endphp
<form method="GET" action="{{ route('employees.index') }}" class="card p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-12 col-md-5">
            <label class="form-label" for="employee-search">Tìm nhân viên</label>
            <input id="employee-search" class="form-control" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Mã NV, họ tên, email, chức vụ...">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label" for="employee-department">Phòng ban</label>
            <select id="employee-department" class="form-select" name="department_id">
                <option value="">Tất cả phòng ban</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) ($filters['department_id'] ?? '') === (string) $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-2">
            <label class="form-label" for="employee-status">Trạng thái</label>
            <select id="employee-status" class="form-select" name="status">
                <option value="">Tất cả</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Còn làm việc</option>
                <option value="on_leave" @selected(($filters['status'] ?? '') === 'on_leave')>Tạm nghỉ</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Nghỉ việc</option>
            </select>
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
            <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
        </div>
    </div>
</form>

<div class="card">
    @if($employees->count())
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Mã NV</th>
                        <th>Tên</th>
                        @unless($directorView)
                            <th>Email</th>
                        @endunless
                        <th>Chức vụ</th>
                        <th>Phòng ban</th>
                        @if($directorView)
                            <th>Ngày vào làm</th>
                            <th>Hợp đồng</th>
                        @endif
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @php $pendingEmployeeDeletions = $pendingEmployeeDeletions ?? collect(); @endphp
            @foreach($employees as $employee)
                        @php $latestContract = $employee->contracts->first(); @endphp
                        <tr>
                            <td><code>{{ $employee->employee_code ?? '—' }}</code></td>
                            <td class="fw-semibold">{{ $employee->name }}</td>
                            @unless($directorView)
                                <td>{{ $employee->email }}</td>
                            @endunless
                            <td>{{ $employee->position ?? '-' }}</td>
                            <td>{{ $employee->department ? '[' . $employee->department->code . '] ' . $employee->department->name : 'Chưa xác định' }}</td>
                            @if($directorView)
                                <td>{{ optional($employee->start_date)->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ $contractStatusLabel($latestContract?->status) }}</td>
                            @endif
                            <td>
                                @if($employee->status === 'active')
                                    <span class="badge bg-success-subtle text-success-emphasis">{{ $employee->statusLabel() }}</span>
                                @elseif($employee->status === 'inactive')
                                    <span class="badge bg-danger-subtle text-danger-emphasis">{{ $employee->statusLabel() }}</span>
                                @elseif($employee->status === 'on_leave')
                                    <span class="badge bg-warning-subtle text-warning-emphasis">{{ $employee->statusLabel() }}</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ $employee->statusLabel() }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-outline-primary">Xem</a>
                                @if(auth()->user()?->canManageHr())
                                <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-outline-secondary">Sửa</a>
                                <form method="POST" action="{{ route('employees.destroy', $employee) }}" style="display:inline;" data-confirm="Bạn có chắc muốn xóa?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                </form>
                                @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($employees->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $employees->links() }}
            </div>
        @endif
    @else
        <div class="text-center py-5 text-muted">
            <p>Không có nhân viên nào</p>
        </div>
    @endif
</div>

@endsection
