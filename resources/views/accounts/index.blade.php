@extends('layouts.app')

@section('title', 'Quản lý tài khoản')

@section('content')
    <div class="page-head">
        <div>
            <h1>Quản lý tài khoản</h1>
            <p class="muted">Danh sách và quản lý tài khoản người dùng hệ thống. Tài khoản gắn hồ sơ nhân viên đã xóa được đánh dấu để xóa.</p>
        </div>
        <div class="page-actions">
            <a class="btn" href="{{ route('admin.notifications.index') }}">Thông báo</a>
            <a class="btn primary" href="{{ route('accounts.create') }}">Tạo tài khoản</a>
            <a class="btn" href="{{ route('permissions.index') }}">Quản lý phân quyền</a>
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
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        @php $pendingEmployeeLabel = ($pendingAccountIds ?? collect())[$user->id] ?? null; @endphp
                        <tr @if($pendingEmployeeLabel) style="background:#fff7ed;" @endif>
                            <td>
                                {{ $user->name }}
                                @if($pendingEmployeeLabel)
                                    <div class="muted" style="font-size:13px;color:#c2410c;">Cần xóa tài khoản — hồ sơ {{ $pendingEmployeeLabel }} đã được Giám đốc duyệt xóa</div>
                                @endif
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if ($user->is_admin)
                                    <span class="badge">Admin (hệ thống)</span>
                                @endif
                                @if ($user->is_director)
                                    <span class="badge">Giám đốc</span>
                                @endif
                                @if ($user->is_hr)
                                    <span class="badge">HR</span>
                                @endif
                                @if ($user->is_accountant)
                                    <span class="badge">Kế toán</span>
                                @endif
                                @if (! $user->is_admin && ! $user->is_director && ! $user->is_hr && ! $user->is_accountant)
                                    <span class="badge">Người dùng</span>
                                @endif
                            </td>
                            <td>{{ $user->created_at?->format('d/m/Y') ?? '-' }}</td>
                            <td>
                                <div class="table-actions">
                                    <a class="btn" href="{{ route('accounts.edit', $user) }}">Sửa</a>
                                    <form action="{{ route('accounts.destroy', $user) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn {{ $pendingEmployeeLabel ? 'danger' : '' }}" type="submit" onclick="return confirm('Bạn có chắc muốn xoá tài khoản này?')">{{ $pendingEmployeeLabel ? 'Xóa tài khoản' : 'Xoá' }}</button>
                                    </form>
                                    <form action="{{ route('accounts.toggle_lock', $user) }}" method="POST">
                                        @csrf
                                        <button class="btn" type="submit">{{ $user->is_locked ? 'Mở khoá' : 'Khoá' }}</button>
                                    </form>
                                </div>
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
