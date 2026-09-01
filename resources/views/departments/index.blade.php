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
                    <th style="width:50px">STT</th>
                    <th>Tên phòng ban</th>
                    <th style="width:120px">Mã phòng ban</th>
                    <th>Mô tả chức năng</th>
                    <th style="width:160px">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($departments as $i => $department)
                    <tr>
                        <td>{{ $departments->firstItem() + $i }}</td>
                        <td><strong><a class="btn link" href="{{ route('departments.show', $department) }}" style="padding:0;">{{ $department->name }}</a></strong></td>
                        <td><span class="badge bg-secondary">{{ $department->code }}</span></td>
                        <td>{{ $department->description ?: '-' }}</td>
                        <td>
                            <div class="actions">
                                <a class="btn link" href="{{ route('departments.show', $department) }}">Xem</a>
                                <a class="btn" href="{{ route('departments.edit', $department) }}">Sửa</a>
                                <a class="btn danger" href="{{ route('deletion_requests.create', ['kind' => 'department', 'target' => $department->id]) }}">Yêu cầu xóa</a>
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
