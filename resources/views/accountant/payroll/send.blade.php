@extends('layouts.app')

@section('title', 'Gửi bảng lương')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('accountant.dashboard') }}">Kế toán</a></li>
<li>Gửi bảng lương</li>
@endsection

<div class="page-head">
    <div>
        <h1>Gửi bảng lương</h1>
        <p class="muted">Gửi email xác nhận lương để nhân viên xem và xác nhận phiếu lương.</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('accountant.payroll.index') }}">Quay lại</a>
    </div>
</div>

@if(session('success'))
    <div class="alert">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert" style="background:#fee2e2;color:#dc2626;">{{ session('error') }}</div>
@endif

<div class="card">
    <form method="POST" action="{{ route('accountant.payroll.send_all') }}">
        @csrf
        <p class="muted">Chỉ gửi cho các phiếu Giám đốc đã duyệt, chưa thanh toán. Nhân viên xác nhận trên cổng hoặc qua email.</p>
        <button class="btn primary" type="submit">Gửi email xác nhận</button>
    </form>
</div>

@endsection
