@extends('layouts.app')

@section('title', 'Dashboard Kế toán')

@section('breadcrumb')
<li><a href="{{ route('accountant.dashboard') }}">Kế toán</a></li>
<li>Dashboard</li>
@endsection

@section('content')
<div class="emp-hero">
    <div>
        <h1>Dashboard Kế toán</h1>
        <p>Tổng quan nhanh về bảng lương — tính, theo dõi trạng thái và thanh toán.</p>
    </div>
    <div class="emp-hero-meta">
        <span class="emp-chip"><i class="bi bi-calendar3"></i> {{ ['Chủ nhật','Thứ hai','Thứ ba','Thứ tư','Thứ năm','Thứ sáu','Thứ bảy'][now()->dayOfWeek] }}, {{ now()->format('d/m/Y') }}</span>
        <span class="emp-chip"><i class="bi bi-calculator"></i> Cổng kế toán</span>
    </div>
</div>

<section class="grid emp-kpis">
    <article class="emp-kpi is-info">
        <div class="emp-kpi-head">
            <h2 class="emp-kpi-label">Tổng phiếu lương</h2>
            <span class="emp-kpi-ico ico-info"><i class="bi bi-table"></i></span>
        </div>
        <div class="emp-kpi-value">{{ $total }}</div>
        <p class="emp-kpi-sub">Tất cả phiếu trong hệ thống</p>
        <a class="btn emp-kpi-cta" href="{{ route('accountant.payroll.index') }}">Xem bảng lương</a>
    </article>
    <article class="emp-kpi is-warn">
        <div class="emp-kpi-head">
            <h2 class="emp-kpi-label">Chờ HR / Giám đốc</h2>
            <span class="emp-kpi-ico ico-warn"><i class="bi bi-hourglass-split"></i></span>
        </div>
        <div class="emp-kpi-value">{{ $waitingReview }}</div>
        <p class="emp-kpi-sub">Đã tính — chờ kiểm tra hoặc duyệt</p>
        <a class="btn emp-kpi-cta" href="{{ route('accountant.payroll.index') }}">Theo dõi phiếu</a>
    </article>
    <article class="emp-kpi is-violet">
        <div class="emp-kpi-head">
            <h2 class="emp-kpi-label">Chờ thanh toán</h2>
            <span class="emp-kpi-ico ico-violet"><i class="bi bi-wallet2"></i></span>
        </div>
        <div class="emp-kpi-value">{{ $waitingPay }}</div>
        <p class="emp-kpi-sub">NV đã xác nhận — đủ điều kiện chi</p>
        <a class="btn primary emp-kpi-cta" href="{{ route('payroll.index') }}">Thanh toán lương</a>
    </article>
    <article class="emp-kpi is-ok">
        <div class="emp-kpi-head">
            <h2 class="emp-kpi-label">Đã thanh toán</h2>
            <span class="emp-kpi-ico ico-ok"><i class="bi bi-check2-circle"></i></span>
        </div>
        <div class="emp-kpi-value">{{ $paid }}</div>
        <p class="emp-kpi-sub">Phiếu đã chi trong hệ thống</p>
        <a class="btn emp-kpi-cta" href="{{ route('salary_histories.index') }}">Lịch sử lương</a>
    </article>
</section>

<div class="card" style="margin-top:22px;">
    <h2 style="margin:0 0 14px; font-size:1.05rem;">Thao tác nhanh</h2>
    <div class="emp-actions">
        <a class="emp-action" href="{{ route('accountant.payroll.generate') }}">
            <i class="bi bi-calculator ico-info"></i> Tính lương
        </a>
        <a class="emp-action" href="{{ route('accountant.payroll.index') }}">
            <i class="bi bi-table ico-ok"></i> Quản lý bảng lương
        </a>
        <a class="emp-action" href="{{ route('payroll.index') }}">
            <i class="bi bi-wallet2 ico-violet"></i> Thanh toán lương
        </a>
        <a class="emp-action" href="{{ route('accountant.payroll.feedback') }}">
            <i class="bi bi-exclamation-triangle ico-warn"></i> Sự cố lương
        </a>
    </div>
</div>
@endsection
