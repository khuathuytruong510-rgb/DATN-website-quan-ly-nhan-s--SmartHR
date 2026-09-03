@extends('layouts.app')

@section('title', 'Quản lý tài khoản')

@section('content')
    @php
        $filters = $filters ?? ['q' => '', 'employee_code' => '', 'status' => ''];
        $hasFilters = ($filters['q'] ?? '') !== ''
            || ($filters['employee_code'] ?? '') !== ''
            || ($filters['status'] ?? '') !== '';
    @endphp

    <div class="page-head">
        <div>
            <h1>Quản lý tài khoản</h1>
        </div>
        <div class="page-actions">
            <a class="btn" href="{{ route('admin.notifications.index') }}">Thông báo</a>
            <a class="btn primary" href="{{ route('accounts.create') }}">Tạo tài khoản</a>
            <a class="btn" href="{{ route('permissions.index') }}">Quản lý phân quyền</a>
        </div>
    </div>

    <div class="card filter-card">
        <form method="GET" action="{{ route('accounts.index') }}" class="filter-form">
            <div class="field-group">
                <label class="form-label" for="accountSearch">Tìm tài khoản</label>
                <input id="accountSearch" class="form-control" name="q" value="{{ $filters['q'] }}" placeholder="Họ tên hoặc email...">
            </div>
            <div class="field-group">
                <label class="form-label" for="accountEmployeeCode">Mã nhân viên</label>
                <input id="accountEmployeeCode" class="form-control" name="employee_code" value="{{ $filters['employee_code'] }}" placeholder="VD: NV001">
            </div>
            <div class="field-group">
                <label class="form-label" for="accountStatus">Trạng thái</label>
                <select id="accountStatus" class="form-select" name="status">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Đang hoạt động</option>
                    <option value="locked" {{ ($filters['status'] ?? '') === 'locked' ? 'selected' : '' }}>Đã khóa</option>
                </select>
            </div>
            <div class="field-group actions-row">
                <button type="submit" class="btn primary">Tìm kiếm</button>
                <a class="btn" href="{{ route('accounts.index') }}">Xóa lọc</a>
            </div>
        </form>
    </div>

    <div class="card">
        @if ($users->isEmpty())
            <div class="empty">
                {{ $hasFilters ? 'Không tìm thấy tài khoản phù hợp bộ lọc.' : 'Chưa có tài khoản nào.' }}
            </div>
            @if($hasFilters)
                <div style="margin-top:12px;">
                    <a class="btn" href="{{ route('accounts.index') }}">Xóa lọc</a>
                </div>
            @endif
        @else
            <div class="table-responsive">
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
                            @php
                                $pendingEmployeeLabel = ($pendingAccountIds ?? collect())[$user->id] ?? null;
                                $isLocked = (bool) $user->is_locked;
                            @endphp
                            <tr @if($pendingEmployeeLabel) style="background:#fff7ed;" @endif>
                                <td>
                                    <strong>{{ $user->name }}</strong>
                                    @if(optional($user->employee)->employee_code)
                                        <div class="muted" style="font-size:12px;">{{ $user->employee->employee_code }}</div>
                                    @endif
                                    @if($pendingEmployeeLabel)
                                        <div class="muted" style="font-size:13px;color:#c2410c;">Đã khóa đăng nhập — hồ sơ {{ $pendingEmployeeLabel }} đã nghỉ việc</div>
                                    @endif
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @if ($user->is_admin)
                                        <span class="badge info">Admin</span>
                                    @endif
                                    @if ($user->is_director)
                                        <span class="badge director">Giám đốc</span>
                                    @endif
                                    @if ($user->is_hr)
                                        <span class="badge info">HR</span>
                                    @endif
                                    @if ($user->is_accountant)
                                        <span class="badge info">Kế toán</span>
                                    @endif
                                    @if (! $user->is_admin && ! $user->is_director && ! $user->is_hr && ! $user->is_accountant)
                                        <span class="badge info">Người dùng</span>
                                    @endif
                                </td>
                                <td>
                                    @if($isLocked)
                                        <span class="badge danger">Đã khóa</span>
                                    @else
                                        <span class="badge ok">Đang hoạt động</span>
                                    @endif
                                </td>
                                <td>{{ $user->created_at?->format('d/m/Y') ?? '—' }}</td>
                                <td>
                                    <div class="table-actions">
                                        <a class="btn" href="{{ route('accounts.edit', $user) }}">Sửa</a>
                                        <form action="{{ route('accounts.destroy', $user) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn {{ $pendingEmployeeLabel ? 'danger' : '' }}" type="submit" data-confirm="Bạn có chắc muốn xoá tài khoản này?" data-confirm-variant="danger">{{ $pendingEmployeeLabel ? 'Xóa tài khoản' : 'Xoá' }}</button>
                                        </form>
                                        <form action="{{ route('accounts.toggle_lock', $user) }}" method="POST">
                                            @csrf
                                            <button class="btn" type="submit" data-confirm="{{ $isLocked ? 'Mở khóa tài khoản này?' : 'Khóa tài khoản này? Tài khoản sẽ không đăng nhập được.' }}">{{ $isLocked ? 'Mở khoá' : 'Khoá' }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection
