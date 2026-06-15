@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Nhân viên</h1>
    <a class="btn" href="{{ route('employees.create') }}">Tạo nhân viên</a>

    @if($employees->count())
        <table class="table">
            <thead>
                <tr>
                    <th>Tên</th>
                    <th>Email</th>
                    <th>Chức vụ</th>
                    <th>Phòng ban</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $employee)
                    <tr>
                        <td>{{ $employee->name }}</td>
                        <td>{{ $employee->email }}</td>
                        <td>{{ $employee->position }}</td>
                        <td>{{ optional($employee->department)->name }}</td>
                        <td>{{ $employee->status }}</td>
                        <td>
                            <a href="{{ route('employees.edit', $employee) }}">Sửa</a>
                            <form action="{{ route('employees.destroy', $employee) }}" method="POST" style="display:inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Xóa nhân viên?')">Xóa</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $employees->links() }}
    @else
        <p>Chưa có nhân viên.</p>
    @endif
</div>
@endsection
