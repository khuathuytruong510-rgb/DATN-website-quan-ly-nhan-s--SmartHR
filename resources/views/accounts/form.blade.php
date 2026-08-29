@extends('layouts.app')

@section('title', 'Tạo tài khoản')

@section('content')
    <div class="page-head">
        <div>
            <h1>Tạo tài khoản</h1>
            <p class="muted">Chỉ admin được tạo tài khoản đăng nhập cho hệ thống.</p>
        </div>
        <a class="btn" href="{{ route('accounts.index') }}">Quay lại</a>
    </div>

    <div class="card">
        <form method="POST" action="{{ isset($user) && $user->id ? route('accounts.update', $user) : route('accounts.store') }}">
            @csrf
            @if(isset($user) && $user->id)
                @method('PUT')
            @endif
            <div class="grid two-cols">
                <div class="field">
                    <label for="name">Họ tên</label>
                    <input id="name" name="name" value="{{ old('name', $user->name ?? '') }}" required autofocus>
                    @error('name')<span class="error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email ?? '') }}" required>
                    @error('email')<span class="error">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="grid two-cols">
                <div class="field">
                    <label for="password">Mật khẩu</label>
                    <input id="password" name="password" type="password" {{ isset($user) && $user->id ? '' : 'required' }}>
                    @error('password')<span class="error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="password_confirmation">Nhập lại mật khẩu</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" {{ isset($user) && $user->id ? '' : 'required' }}>
                </div>
            </div>

            <div class="field">
                <label for="role">Vai trò hệ thống</label>
                @php
                    $currentRole = old('role', isset($user) && $user->id
                        ? ($user->is_admin ? 'admin' : ($user->is_director ? 'director' : ($user->is_hr ? 'hr' : ($user->is_accountant ? 'accountant' : 'employee'))))
                        : 'employee');
                @endphp
                <select id="role" name="role" required>
                    <option value="employee" {{ $currentRole === 'employee' ? 'selected' : '' }}>Nhân viên</option>
                    <option value="hr" {{ $currentRole === 'hr' ? 'selected' : '' }}>HR</option>
                    <option value="accountant" {{ $currentRole === 'accountant' ? 'selected' : '' }}>Kế toán</option>
                    <option value="director" {{ $currentRole === 'director' ? 'selected' : '' }}>Giám đốc</option>
                    <option value="admin" {{ $currentRole === 'admin' ? 'selected' : '' }}>Admin (quản trị hệ thống)</option>
                </select>
                <p class="muted" style="margin:6px 0 0;font-size:13px;">Admin quản trị CNTT. Giám đốc phê duyệt nghiệp vụ. Không gán cả hai cho cùng một người.</p>
                @error('role')<span class="error">{{ $message }}</span>@enderror
            </div>

            <div class="field">
                <label for="department_id">Phòng ban</label>
                <select id="department_id" name="department_id">
                    <option value="">Chọn phòng ban</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" {{ old('department_id', $user->employee->department_id ?? '') == $department->id ? 'selected' : '' }}>[{{ $department->code }}] {{ $department->name }}</option>
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
