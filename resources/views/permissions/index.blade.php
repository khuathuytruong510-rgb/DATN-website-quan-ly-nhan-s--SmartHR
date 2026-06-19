@extends('layouts.app')

@section('title', 'Phân quyền')

@section('content')
    <div class="page-head">
        <div>
            <h1>Phân quyền</h1>
            <p class="muted">Gán vai trò Admin hoặc HR cho người dùng.</p>
        </div>
        <div>
            <a class="btn link" href="{{ route('accounts.index') }}">Xem danh sách tài khoản</a>
        </div>
    </div>

    <div class="card">
        @if ($users->isEmpty())
            <div class="empty">Chưa có tài khoản nào để phân quyền.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Họ tên</th>
                        <th>Email</th>
                        <th>Admin</th>
                        <th>HR</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->is_admin ? 'Có' : 'Không' }}</td>
                            <td>{{ $user->is_hr ? 'Có' : 'Không' }}</td>
                            <td>
                                <form method="POST" action="{{ route('permissions.update', $user) }}" style="display:inline-flex; gap: 8px; align-items:center;">
                                    @csrf
                                    @method('PUT')
                                    <label style="display:inline-flex; align-items:center; gap: 6px;">
                                        <input type="checkbox" name="is_admin" value="1" {{ $user->is_admin ? 'checked' : '' }}>
                                        Admin
                                    </label>
                                    <label style="display:inline-flex; align-items:center; gap: 6px;">
                                        <input type="checkbox" name="is_hr" value="1" {{ $user->is_hr ? 'checked' : '' }}>
                                        HR
                                    </label>
                                    <button class="btn primary" type="submit">Lưu</button>
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
