@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>👥 Nhân viên</h1>
        <p class="muted">Quản lý thông tin nhân viên và vị trí.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('employees.create') }}">+ Tạo nhân viên</a>
</div>

<div class="card">
    @if($employees->count())
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Tên</th>
                        <th>Email</th>
                        <th>Chức vụ</th>
                        <th>Phòng ban</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $employee)
                        <tr>
                            <td class="fw-semibold">{{ $employee->name }}</td>
                            <td>{{ $employee->email }}</td>
                            <td>{{ $employee->position ?? '-' }}</td>
                            <td>{{ optional($employee->department)->name ?? '-' }}</td>
                            <td>
                                @if($employee->status === 'active')
                                    <span class="badge bg-success-subtle text-success-emphasis">Active</span>
                                @elseif($employee->status === 'inactive')
                                    <span class="badge bg-danger-subtle text-danger-emphasis">Inactive</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ ucfirst($employee->status) }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-outline-primary">Xem</a>
                                <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-outline-secondary">Sửa</a>
                                <form method="POST" action="{{ route('employees.destroy', $employee) }}" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn xóa?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                </form>
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

<style>
    .bg-success-subtle { background-color: #e6f4ea !important; }
    .text-success-emphasis { color: #137333 !important; }
    .bg-danger-subtle { background-color: #fce8e6 !important; }
    .text-danger-emphasis { color: #d93025 !important; }
    .bg-secondary-subtle { background-color: #e8eaed !important; }
    .text-secondary-emphasis { color: #3c4043 !important; }
</style>
@endsection
