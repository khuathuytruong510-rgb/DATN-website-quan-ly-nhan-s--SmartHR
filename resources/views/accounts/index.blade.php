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
            @if (auth()->user()->is_admin)
                <a class="btn" href="{{ route('permissions.index') }}">Quản lý phân quyền</a>
            @endif
        </div>
    </div>

    @php $filters = $filters ?? []; @endphp
    <form method="GET" action="{{ route('accounts.index') }}" class="card p-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-6">
                <label class="form-label" for="account-search">Tìm tài khoản</label>
                <input id="account-search" class="form-control" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Họ tên hoặc email...">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label" for="account-status">Trạng thái</label>
                <select id="account-status" class="form-select" name="status">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Đang hoạt động</option>
                    <option value="locked" @selected(($filters['status'] ?? '') === 'locked')>Đã khóa</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                <a href="{{ route('accounts.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
            </div>
        </div>
    </form>

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
                            <td>
                                @if($user->is_locked)
                                    <span class="badge" style="background:#fee2e2;color:#b91c1c;">Đã khóa</span>
                                @else
                                    <span class="badge" style="background:#dcfce7;color:#166534;">Đang hoạt động</span>
                                @endif
                            </td>
                            <td>{{ $user->created_at?->format('d/m/Y') ?? '-' }}</td>
                            <td>
                                <a class="btn" href="{{ route('accounts.edit', $user) }}">Sửa</a>
                                
                                <form action="{{ route('accounts.destroy', $user) }}" method="POST" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn" type="submit" data-confirm="Bạn có chắc muốn xoá tài khoản này?">Xoá</button>
                                </form>
                                <form action="{{ route('accounts.toggle_lock', $user) }}" method="POST" style="display:inline">
                                    @csrf
                                    <button class="btn" type="submit">{{ $user->is_locked ? 'Mở khoá' : 'Khoá' }}</button>
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
