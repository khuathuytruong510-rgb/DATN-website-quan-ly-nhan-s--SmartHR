@extends('layouts.app')

@section('title', 'Phân quyền')

@section('content')
    <div class="page-head">
        <div>
            <h1>Phân quyền</h1>
        </div>
        <div class="page-actions">
            <a class="btn" href="{{ route('accounts.index') }}">Xem danh sách tài khoản</a>
        </div>
    </div>

    @php
        $filters = $filters ?? [];
        $canManageAdminRoles = (bool) auth()->user()?->is_super_admin;
    @endphp
    <form method="GET" action="{{ route('permissions.index') }}" class="card p-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label" for="permission-search">Tìm tài khoản</label>
                <input id="permission-search" class="form-control" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Họ tên hoặc email">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label" for="permission-role">Vai trò</label>
                <select id="permission-role" class="form-select" name="role">
                    <option value="" @selected(($filters['role'] ?? '') === '')>Tất cả vai trò</option>
                    <option value="admin" @selected(($filters['role'] ?? '') === 'admin')>Admin</option>
                    <option value="super_admin" @selected(($filters['role'] ?? '') === 'super_admin')>Siêu Admin</option>
                    <option value="director" @selected(($filters['role'] ?? '') === 'director')>Giám đốc</option>
                    <option value="hr" @selected(($filters['role'] ?? '') === 'hr')>HR</option>
                    <option value="accountant" @selected(($filters['role'] ?? '') === 'accountant')>Kế toán</option>
                    <option value="none" @selected(($filters['role'] ?? '') === 'none')>Chưa phân quyền</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label" for="permission-department">Phòng ban</label>
                <select id="permission-department" class="form-select" name="department_id">
                    <option value="" @selected(($filters['department_id'] ?? '') === '')>Tất cả phòng ban</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) ($filters['department_id'] ?? '') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                    <option value="none" @selected(($filters['department_id'] ?? '') === 'none')>Chưa gắn phòng ban</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn primary">Lọc</button>
                <a class="btn" href="{{ route('permissions.index') }}">Xóa lọc</a>
            </div>
        </div>
    </form>

    <div class="card">
        @if ($users->isEmpty())
            <div class="empty">Không có tài khoản nào khớp bộ lọc.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Họ tên</th>
                        <th>Email</th>
                        <th>Admin</th>
                        <th>Siêu Admin</th>
                        <th>Giám đốc</th>
                        <th>HR</th>
                        <th>Kế toán</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->is_admin ? 'Có' : 'Không' }}</td>
                            <td>{{ $user->is_super_admin ? 'Có' : 'Không' }}</td>
                            <td>{{ $user->is_director ? 'Có' : 'Không' }}</td>
                            <td>{{ $user->is_hr ? 'Có' : 'Không' }}</td>
                            <td>{{ $user->is_accountant ? 'Có' : 'Không' }}</td>
                            <td>
                                @if($canManageAdminRoles || (! $user->is_admin && ! $user->is_super_admin))
                                <form method="POST" action="{{ route('permissions.update', $user) }}" class="table-actions">
                                    @csrf
                                    @method('PUT')
                                    @if(($filters['search'] ?? '') !== '')
                                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                                    @endif
                                    @if(($filters['role'] ?? '') !== '')
                                        <input type="hidden" name="role" value="{{ $filters['role'] }}">
                                    @endif
                                    @if(($filters['department_id'] ?? '') !== '')
                                        <input type="hidden" name="department_id" value="{{ $filters['department_id'] }}">
                                    @endif
                                    @if($canManageAdminRoles)
                                        <label class="check-row">
                                            <input type="checkbox" name="is_admin" value="1" {{ $user->is_admin ? 'checked' : '' }}>
                                            Admin
                                        </label>
                                        <label class="check-row" title="Siêu Admin có quyền quản trị toàn hệ thống">
                                            <input type="checkbox" name="is_super_admin" value="1" {{ $user->is_super_admin ? 'checked' : '' }}>
                                            Siêu Admin
                                        </label>
                                    @endif
                                    <label class="check-row" title="Role Giám đốc phải đi theo người đang giữ chức">
                                        <input type="checkbox" name="is_director" value="1" {{ $user->is_director ? 'checked' : '' }} disabled>
                                        Giám đốc
                                    </label>
                                    @if($user->is_director)
                                        <input type="hidden" name="is_director" value="1">
                                    @endif
                                    <label class="check-row">
                                        <input type="checkbox" name="is_hr" value="1" {{ $user->is_hr ? 'checked' : '' }}>
                                        HR
                                    </label>
                                    <label class="check-row">
                                        <input type="checkbox" name="is_accountant" value="1" {{ $user->is_accountant ? 'checked' : '' }}>
                                        Kế toán
                                    </label>
                                    <button class="btn primary" type="submit">Lưu</button>
                                </form>
                                @else
                                    <span class="muted">Chỉ Siêu Admin được sửa tài khoản quản trị.</span>
                                @endif
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
