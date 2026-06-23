@extends('layouts.app')

@section('title', 'Quản lý tài khoản')

@section('content')
    <div class="page-head">
        <div>
            <h1>Quản lý tài khoản</h1>
            <p class="muted">Danh sách và quản lý tài khoản người dùng hệ thống.</p>
        </div>
        <div style="display:flex;gap:10px">
            <a class="btn primary"
            href="{{ route('accounts.create') }}">
            + Tạo tài khoản
            </a>

            <a class="btn"
            href="{{ route('permissions.index') }}">
            Quản lý phân quyền
            </a>
        </div>
    </div>

    <div class="card">
        @if ($users->isEmpty())
            <div class="empty">Chưa có tài khoản nào.</div>
        @else
            <table>
                <thead>
                <tr>
                    <th>Họ tên</th>
                    <th>Email</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>

                    <td>{{ $user->email }}</td>

                    <td>
                        @if ($user->is_admin)
                            <span class="badge">Admin</span>
                        @elseif ($user->is_hr)
                            <span class="badge">HR</span>
                        @else
                            <span class="badge">Nhân viên</span>
                        @endif
                    </td>

                    <td>
                        @if ($user->is_active)
                            <span class="badge">Hoạt động</span>
                        @else
                            <span class="badge">Đã khóa</span>
                        @endif
                    </td>

                    <td>
                        {{ $user->created_at?->format('d/m/Y') }}
                    </td>

                    <td style="display:flex;gap:8px;">

                        <a href="{{ route('accounts.edit',$user->id) }}"
                            class="btn">
                            Sửa
                        </a>

                        <form method="POST"
                            action="{{ route('accounts.toggle',$user->id) }}">
                            @csrf
                            @method('PUT')

                            <button type="submit"
                                class="btn warning">

                                {{ $user->is_active ? 'Khóa' : 'Mở khóa' }}

                            </button>
                        </form>

                        <form method="POST"
                            action="{{ route('accounts.destroy',$user->id) }}"
                            onsubmit="return confirm('Xóa tài khoản này?')">

                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                class="btn danger">

                                Xóa

                            </button>
                        </form>

                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
            <div class="pagination">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection
