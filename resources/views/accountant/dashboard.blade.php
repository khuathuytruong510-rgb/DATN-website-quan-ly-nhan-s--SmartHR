@extends('layouts.app')

@section('title', 'Kế toán - Dashboard')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('accountant.dashboard') }}">Kế toán</a></li>
<li>Dashboard</li>
@endsection

<div class="page-head">
    <div>
        <h1>Dashboard Kế toán</h1>
        <p class="muted">Tổng quan nhanh về bảng lương</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('accountant.payroll.index') }}">Quản lý bảng lương</a>
    </div>
</div>

<div class="grid stats">
    <div class="card">
        <div class="muted">Tổng bảng lương</div>
        <div class="stat-value">{{ $total }}</div>
    </div>
    <div class="card">
        <div class="muted">Chờ duyệt</div>
        <div class="stat-value">{{ $pending }}</div>
    </div>
    <div class="card">
        <div class="muted">Đã duyệt</div>
        <div class="stat-value">{{ $approved }}</div>
    </div>
</div>

@endsection
