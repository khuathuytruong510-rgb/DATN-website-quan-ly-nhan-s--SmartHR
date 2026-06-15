@extends('layouts.app')

@section('title', 'Phòng ban - SmartHR')

@section('content')
    <div class="page-head">
        <div>
            <h1>Phòng ban</h1>
            <p class="muted">Quản lý danh sách phòng ban trong công ty.</p>
        </div>
        <a class="btn primary" href="{{ route('departments.create') }}">Thêm phòng ban</a>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Tên phòng ban</th>
                    <th>Trưởng phòng</th>
                    <th>Nhân viên</th>
                    <th>Mô tả</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($departments as $department)
                    <tr>
                        <td><strong>{{ $department->name }}</strong></td>
                        <td>{{ $department->manager ?: '-' }}</td>
                        <td>{{ $department->employee_count }}</td>
                        <td>{{ $department->description ?: '-' }}</td>
                        <td>
                            <div class="actions">
                                <a class="btn" href="{{ route('departments.edit', $department) }}">Sửa</a>
                                <form method="POST" action="{{ route('departments.destroy', $department) }}" onsubmit="return confirm('Xóa phòng ban này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn danger" type="submit">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5"><div class="empty">Chưa có phòng ban.</div></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">{{ $departments->links() }}</div>
    </div>
@endsection
