@extends('layouts.app')

@section('title', 'Đổi mật khẩu')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('me.dashboard') }}">Dashboard</a></li>
<li>Đổi mật khẩu</li>
@endsection
<div class="page-head">
    <div>
        <h1>Đổi mật khẩu</h1>
        <p class="muted">Thay đổi mật khẩu tài khoản của bạn</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('me.profile') }}">Quay lại</a>
    </div>
</div>

<div class="card">
    <form method="POST" action="{{ route('me.password.update') }}">
        @csrf
        <div class="field">
            <label>Mật khẩu hiện tại</label>
            <input type="password" name="current_password">
            @error('current_password')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label>Mật khẩu mới</label>
            <input type="password" name="password">
            @error('password')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label>Xác nhận mật khẩu</label>
            <input type="password" name="password_confirmation">
        </div>

        <div class="actions">
            <button class="btn primary" type="submit">Đổi mật khẩu</button>
        </div>
    </form>
</div>

@endsection
