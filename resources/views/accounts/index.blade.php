@extends('layouts.app')

@section('title', 'Quản lý tài khoản')

@section('content')
    <div class="page-head">
        <div>
            <h1>Quản lý tài khoản</h1>
            <p class="muted">Danh sách và quản lý tài khoản người dùng hệ thống.</p>
        </div>
        <div>
            <a class="btn primary" href="{{ route('permissions.index') }}">Quản lý phân quyền</a>
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
                        <th>Ngày tạo</th>
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
                                @endif
                                @if ($user->is_hr)
                                    <span class="badge">HR</span>
                                @endif
                                @if (! $user->is_admin && ! $user->is_hr)
                                    <span class="badge">Người dùng</span>
                                @endif
                            </td>
                            <td>{{ $user->created_at?->format('d/m/Y') ?? '-' }}</td>
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
