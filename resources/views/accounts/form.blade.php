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
        <form method="POST" action="{{ route('accounts.store') }}">
            @csrf
            <div class="grid two-cols">
                <div class="field">
                    <label for="name">Họ tên</label>
                    <input id="name" name="name" value="{{ old('name') }}" required autofocus>
                    @error('name')<span class="error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                    @error('email')<span class="error">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="grid two-cols">
                <div class="field">
                    <label for="password">Mật khẩu</label>
                    <input id="password" name="password" type="password" required>
                    @error('password')<span class="error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="password_confirmation">Nhập lại mật khẩu</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required>
                </div>
            </div>

            <div class="field">
                <label for="role">Vai trò</label>
                <select id="role" name="role" required>
                    <option value="employee" {{ old('role') === 'employee' ? 'selected' : '' }}>Nhân viên</option>
                    <option value="hr" {{ old('role') === 'hr' ? 'selected' : '' }}>HR</option>
                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
                @error('role')<span class="error">{{ $message }}</span>@enderror
            </div>

            <div class="actions">
                <button class="btn primary" type="submit">Tạo tài khoản</button>
                <a class="btn" href="{{ route('accounts.index') }}">Hủy</a>
            </div>
        </form>
    </div>
@endsection
