@extends('layouts.app')

@section('title', 'Đăng nhập - SmartHR')

@section('content')
    <div class="auth-card">
        <p class="auth-brand">SmartHR</p>
        <h1>Đăng nhập</h1>

        <form method="POST" action="{{ route('login.store') }}" class="auth-form form-stack">
            @csrf
            <div class="field">
                <label for="email">Email</label>
                <input id="email" class="form-control" name="email" type="email" value="{{ old('email') }}" required autofocus>
                @error('email') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="password">Mật khẩu</label>
                <input id="password" class="form-control" name="password" type="password" required>
                @error('password') <div class="error">{{ $message }}</div> @enderror
            </div>

            <label class="remember-row">
                <input type="checkbox" name="remember" value="1">
                Ghi nhớ đăng nhập
            </label>

            <button class="btn primary" type="submit" style="width: 100%;">Đăng nhập</button>
        </form>

        <div class="callout" style="margin-top:18px;">
            <p class="callout-title">Tài khoản demo (mật khẩu: 123456)</p>
            <p style="margin:0;font-size:13px;line-height:1.7;">
                HR — hr@smarthr.com<br>
                Kế toán — accountant@smarthr.com<br>
                Giám đốc — giamdoc@smarthr.com<br>
                Nhân viên — nv@smarthr.com
            </p>
        </div>

        <p class="muted" style="margin-top: 18px;">
            Chưa có tài khoản?
            <a href="{{ route('register') }}">Đăng ký</a>
        </p>
    </div>
@endsection
