@extends('layouts.app')

@section('title', 'Tạo tài khoản')

@section('content')
    <div class="page-head">
        <div>
            <h1>Tạo tài khoản</h1>
            <p class="muted">{{ !empty($linkEmployee) ? 'Kết nối tài khoản với hồ sơ nhân sự đã có. Role Director cấp ở trang Người giữ chức nếu đã có Giám đốc.' : 'Chỉ admin được tạo tài khoản đăng nhập cho hệ thống.' }}</p>
        </div>
        <div class="page-actions">
            <a class="btn" href="{{ !empty($linkEmployee) ? route('director_succession.index') : route('accounts.index') }}">Quay lại</a>
        </div>
    </div>

    <div class="card">
        @if(!empty($linkEmployee))
            <div class="callout info" style="margin-bottom:16px;">
                Kết nối hồ sơ <strong>{{ $linkEmployee->name }}</strong>
                @if($linkEmployee->employee_code) (<code>{{ $linkEmployee->employee_code }}</code>) @endif
                — {{ $linkEmployee->position ?: '—' }}
                @if($linkEmployee->department) · {{ $linkEmployee->department->name }} @endif.
                Không tạo thêm hồ sơ mới và không đổi tên tài khoản Giám đốc cũ.
            </div>
        @endif
        <form method="POST" action="{{ isset($user) && $user->id ? route('accounts.update', $user) : route('accounts.store') }}">
            @csrf
            @if(isset($user) && $user->id)
                @method('PUT')
            @endif
            @if(!empty($linkEmployee))
                <input type="hidden" name="employee_id" value="{{ $linkEmployee->id }}">
            @endif
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <div class="field">
                        <label class="form-label" for="name">Họ tên</label>
                        <input id="name" class="form-control" name="name" value="{{ old('name', $user->name ?? '') }}" required autofocus>
                        @error('name')<span class="error">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="field">
                        <label class="form-label" for="email">Email</label>
                        <input id="email" class="form-control" name="email" type="email" value="{{ old('email', $user->email ?? '') }}" required>
                        @error('email')<span class="error">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="field">
                        <label class="form-label" for="password">Mật khẩu</label>
                        <input id="password" class="form-control" name="password" type="password" {{ isset($user) && $user->id ? '' : 'required' }}>
                        @error('password')<span class="error">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="field">
                        <label class="form-label" for="password_confirmation">Nhập lại mật khẩu</label>
                        <input id="password_confirmation" class="form-control" name="password_confirmation" type="password" {{ isset($user) && $user->id ? '' : 'required' }}>
                    </div>
                </div>
            </div>

            <div class="field">
                <label class="form-label" for="role">Vai trò hệ thống</label>
                @php
                    $isEdit = isset($user) && $user->id;
                    $currentRole = old('role', $isEdit
                        ? ($user->is_admin ? 'admin' : ($user->is_director ? 'director' : ($user->is_hr ? 'hr' : ($user->is_accountant ? 'accountant' : 'employee'))))
                        : 'employee');
                    $lockDirectorRole = $isEdit && $user->is_director;
                    $blockNewDirector = ! empty($directorExists) && $currentRole !== 'director';
                @endphp
                @if($lockDirectorRole)
                    <input type="hidden" name="role" value="director">
                    <select id="role" class="form-select" disabled>
                        <option value="director" selected>Giám đốc</option>
                    </select>
                    <p class="muted" style="margin:6px 0 0;font-size:13px;">Role Giám đốc không sửa tại đây. Dùng <a href="{{ route('director_succession.index') }}">Cập nhật người giữ chức Giám đốc</a> sau quyết định của doanh nghiệp.</p>
                @else
                    <select id="role" class="form-select" name="role" required>
                        <option value="employee" {{ $currentRole === 'employee' ? 'selected' : '' }}>Nhân viên</option>
                        <option value="hr" {{ $currentRole === 'hr' ? 'selected' : '' }}>HR</option>
                        <option value="accountant" {{ $currentRole === 'accountant' ? 'selected' : '' }}>Kế toán</option>
                        <option value="director" {{ $currentRole === 'director' ? 'selected' : '' }} {{ $blockNewDirector ? 'disabled' : '' }}>Giám đốc</option>
                        <option value="admin" {{ $currentRole === 'admin' ? 'selected' : '' }}>Admin (quản trị hệ thống)</option>
                    </select>
                    <p class="muted" style="margin:6px 0 0;font-size:13px;">
                        Admin quản trị CNTT. Giám đốc phê duyệt nghiệp vụ. Không gán cả hai cho cùng một người.
                        @if($blockNewDirector)
                            Đã có người giữ role Director — hãy <a href="{{ route('director_succession.index') }}">cập nhật người giữ chức</a>, không tạo thêm tài khoản Giám đốc và không đổi tên tài khoản cũ.
                        @endif
                    </p>
                @endif
                @error('role')<span class="error">{{ $message }}</span>@enderror
            </div>

            <div class="field">
                <label class="form-label" for="department_id">Phòng ban</label>
                <select id="department_id" class="form-select" name="department_id">
                    <option value="">Chọn phòng ban</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" {{ old('department_id', $linkEmployee->department_id ?? $user->employee->department_id ?? '') == $department->id ? 'selected' : '' }}>[{{ $department->code }}] {{ $department->name }}</option>
                    @endforeach
                </select>
                @error('department_id')<span class="error">{{ $message }}</span>@enderror
            </div>

            <div class="actions">
                <button class="btn primary" type="submit">{{ isset($user) && $user->id ? 'Cập nhật' : 'Tạo tài khoản' }}</button>
                <a class="btn" href="{{ route('accounts.index') }}">Hủy</a>
            </div>
        </form>
    </div>
@endsection
