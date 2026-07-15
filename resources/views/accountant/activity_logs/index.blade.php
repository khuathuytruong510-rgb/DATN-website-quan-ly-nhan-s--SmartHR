@extends('layouts.app')

@section('title', 'Nhật ký hoạt động')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('accountant.dashboard') }}">Kế toán</a></li>
<li>Nhật ký hoạt động</li>
@endsection

<div class="page-head">
    <div>
        <h1>Nhật ký hoạt động</h1>
        <p class="muted">Các hành động liên quan đến lương</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('accountant.dashboard') }}">Quay lại</a>
    </div>
</div>

<div class="card">
    <div class="empty">Không có nhật ký nào.</div>
</div>

@endsection
