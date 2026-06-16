@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>Nhân viên</h1>
        <p class="muted">Quản lý thông tin nhân viên và vị trí.</p>
    </div>
    <a class="btn primary" href="{{ route('employees.create') }}">Tạo nhân viên</a>
</div>

<div class="card">
    @if($employees->count())
        <table>
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
                        <td><strong>{{ $employee->name }}</strong></td>
                        <td>{{ $employee->email }}</td>
                        <td>{{ $employee->position }}</td>
                        <td>{{ optional($employee->department)->name }}</td>
                        <td>
                            <span class="badge {{ $employee->status === 'inactive' ? 'inactive' : '' }}">
                                {{ ucfirst($employee->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a class="btn link" href="{{ route('employees.show', $employee) }}">Xem</a>
                                <a class="btn" href="{{ route('employees.edit', $employee) }}">Sửa</a>
                                <form action="{{ route('employees.destroy', $employee) }}" method="POST" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn danger" type="submit" onclick="return confirm('Xóa nhân viên?')">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="pagination">{{ $employees->links() }}</div>
    @else
        <div class="empty">Chưa có nhân viên.</div>
    @endif
</div>
@endsection
