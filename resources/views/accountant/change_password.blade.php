@extends('layouts.app')

@section('title', 'Đổi mật khẩu')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('accountant.dashboard') }}">Kế toán</a></li>
<li>Đổi mật khẩu</li>
@endsection

<div class="page-head">
    <div>
        <h1>Đổi mật khẩu</h1>
        <p class="muted">Cập nhật mật khẩu tài khoản</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('accountant.profile') }}">Quay lại</a>
    </div>
</div>

<div class="card">
    <form method="POST" action="{{ route('accountant.password.update') }}">
        @csrf
        <div class="field">
            <label>Mật khẩu hiện tại</label>
            <input type="password" name="current_password">
        </div>
        <div class="field">
            <label>Mật khẩu mới</label>
            <input type="password" name="password">
        </div>
        <div class="field">
            <label>Xác nhận mật khẩu</label>
            <input type="password" name="password_confirmation">
        </div>
        <button class="btn primary" type="submit">Đổi mật khẩu</button>
    </form>
</div>

@endsection
