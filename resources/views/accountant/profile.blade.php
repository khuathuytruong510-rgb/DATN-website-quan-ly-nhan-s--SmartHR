@extends('layouts.app')

@section('title', 'Hồ sơ kế toán')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('accountant.dashboard') }}">Kế toán</a></li>
<li>Hồ sơ cá nhân</li>
@endsection

<div class="page-head">
    <div>
        <h1>Hồ sơ cá nhân</h1>
        <p class="muted">Thông tin tài khoản kế toán</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('accountant.dashboard') }}">Quay lại</a>
    </div>
</div>

<div class="card">
    <div><strong>Họ tên:</strong> {{ auth()->user()->name }}</div>
    <div><strong>Email:</strong> {{ auth()->user()->email }}</div>
</div>

@endsection
