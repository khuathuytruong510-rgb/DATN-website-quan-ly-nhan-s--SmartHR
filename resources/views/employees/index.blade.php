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
    </div>
    @if(auth()->user()?->canManageHr())
    <div class="page-actions">
        <a class="btn" href="{{ route('transfers.create') }}">Điều chuyển nhân viên</a>
        <a class="btn primary" href="{{ route('employees.create') }}">Tạo nhân viên</a>
    </div>
    @endif
</div>

@php
    $filters = $filters ?? ['q' => '', 'department_id' => null, 'status' => ''];
    $hasFilters = ($filters['q'] ?? '') !== '' || ! empty($filters['department_id']) || ($filters['status'] ?? '') !== '';
@endphp
<div class="card filter-card">
    <form method="GET" action="{{ route('employees.index') }}" class="filter-form">
        <div class="field-group">
            <label class="form-label" for="employeeSearch">Tìm kiếm</label>
            <input id="employeeSearch" class="form-control" name="q" value="{{ $filters['q'] }}" placeholder="Tên, mã NV, email, chức vụ">
        </div>
        <div class="field-group">
            <label class="form-label" for="employeeDepartment">Phòng ban</label>
            <select id="employeeDepartment" class="form-select" name="department_id">
                <option value="">Tất cả phòng ban</option>
                @foreach(($departments ?? []) as $department)
                    <option value="{{ $department->id }}" {{ (string) ($filters['department_id'] ?? '') === (string) $department->id ? 'selected' : '' }}>
                        [{{ $department->code }}] {{ $department->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="field-group">
            <label class="form-label" for="employeeStatus">Trạng thái</label>
            <select id="employeeStatus" class="form-select" name="status">
                <option value="">Tất cả trạng thái</option>
                <option value="awaiting_contract" {{ ($filters['status'] ?? '') === 'awaiting_contract' ? 'selected' : '' }}>Chờ hợp đồng</option>
                <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Còn làm việc</option>
                <option value="on_leave" {{ ($filters['status'] ?? '') === 'on_leave' ? 'selected' : '' }}>Tạm nghỉ</option>
                <option value="pending_termination" {{ ($filters['status'] ?? '') === 'pending_termination' ? 'selected' : '' }}>Chờ nghỉ việc</option>
                <option value="terminated" {{ ($filters['status'] ?? '') === 'terminated' ? 'selected' : '' }}>Đã nghỉ việc</option>
            </select>
        </div>
        <div class="field-group actions-row">
            <button type="submit" class="btn primary">Lọc</button>
            <a class="btn" href="{{ route('employees.index') }}">Xóa lọc</a>
        </div>
    </form>
</div>

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
                                @php
                                    $awaitingContract = $employee->isAwaitingContract();
                                @endphp
                                @if($awaitingContract)
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis">Chờ hợp đồng</span>
                                @elseif($employee->status === 'active')
                                    <span class="badge bg-success-subtle text-success-emphasis">{{ $employee->statusLabel() }}</span>
                                @elseif($employee->status === 'on_leave')
                                    <span class="badge bg-warning-subtle text-warning-emphasis">{{ $employee->statusLabel() }}</span>
                                @elseif(in_array($employee->status, ['terminated', 'inactive', 'pending_termination'], true))
                                    <span class="badge bg-danger-subtle text-danger-emphasis">{{ $employee->statusLabel() }}</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ $employee->statusLabel() }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="table-actions">
                                <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm">Xem</a>
                                @if(auth()->user()?->canManageHr() && \App\Support\RequestApprover::hrMayManage(auth()->user(), $employee))
                                <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm">Sửa</a>
                                @php
                                    $pendingDeletionId = $pendingEmployeeDeletions[$employee->id] ?? null;
                                    $pendingTransferId = ($pendingEmployeeTransfers ?? [])[$employee->id] ?? null;
                                @endphp
                                @if($pendingDeletionId)
                                    <a href="{{ route('deletion_requests.show', $pendingDeletionId) }}" class="btn btn-sm btn-outline-warning">Chờ GĐ duyệt nghỉ việc</a>
                                @elseif($pendingTransferId)
                                    <a href="{{ route('deletion_requests.show', $pendingTransferId) }}" class="btn btn-sm btn-outline-warning">Chờ GĐ duyệt chuyển</a>
                                @elseif($employee->isTerminated())
                                    <span class="muted">Đã nghỉ việc</span>
                                @else
                                    <a href="{{ route('transfers.create', ['employee' => $employee->id]) }}" class="btn btn-sm btn-outline-secondary">Điều chuyển</a>
                                    <a href="{{ route('deletion_requests.create_employee', $employee) }}" class="btn btn-sm btn-outline-danger">Đề nghị nghỉ việc</a>
                                @endif
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
            <p>{{ $hasFilters ? 'Không tìm thấy nhân viên phù hợp bộ lọc.' : 'Không có nhân viên nào' }}</p>
            @if($hasFilters)
                <a class="btn" href="{{ route('employees.index') }}">Xóa lọc</a>
            @endif
        </div>
    @endif
</div>

@endsection
