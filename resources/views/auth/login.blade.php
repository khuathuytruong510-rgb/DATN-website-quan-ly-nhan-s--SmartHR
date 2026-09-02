@extends('layouts.app')

@section('title', 'Đăng nhập - SmartHR')

@section('content')
    <div class="auth-card">
        <p class="auth-brand">SmartHR</p>
        <h1>Đăng nhập</h1>
        <p class="muted">Truy cập hệ thống quản lý nhân sự SmartHR.</p>

        <form method="POST" action="{{ route('login.store') }}" style="margin-top: 22px;">
            @csrf
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
                @error('email') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="password">Mật khẩu</label>
                <input id="password" name="password" type="password" required>
                @error('password') <div class="error">{{ $message }}</div> @enderror
            </div>

            <label style="display: flex; gap: 8px; align-items: center; margin-bottom: 18px;">
                <input type="checkbox" name="remember" value="1" style="width: auto;">
                Ghi nhớ đăng nhập
            </label>

            <button class="btn primary" type="submit" style="width: 100%;">Đăng nhập</button>
        </form>
    </div>
@endsection
