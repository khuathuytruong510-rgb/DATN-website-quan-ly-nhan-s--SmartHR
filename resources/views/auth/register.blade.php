@extends('layouts.app')

@section('title', 'Đăng ký - SmartHR')

@section('content')
    <div class="auth-card">
        <p class="auth-brand">SmartHR</p>
        <h1>Đăng ký</h1>
        <p class="muted">Tạo tài khoản quản trị để sử dụng SmartHR.</p>

        <form method="POST" action="{{ route('register.store') }}" style="margin-top: 22px;">
            @csrf
            <div class="field">
                <label for="name">Họ tên</label>
                <input id="name" name="name" value="{{ old('name') }}" required autofocus>
                @error('name') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                @error('email') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="password">Mật khẩu</label>
                <input id="password" name="password" type="password" required>
                @error('password') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="password_confirmation">Nhập lại mật khẩu</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required>
            </div>

            <button class="btn primary" type="submit" style="width: 100%;">Tạo tài khoản</button>
        </form>

        <p class="muted" style="margin-top: 18px;">
            Đã có tài khoản?
            <a href="{{ route('login') }}">Đăng nhập</a>
        </p>
    </div>
@endsection
