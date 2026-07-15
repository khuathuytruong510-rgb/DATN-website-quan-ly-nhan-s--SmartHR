@extends('layouts.app')

@section('title', 'Báo cáo lương')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('accountant.dashboard') }}">Kế toán</a></li>
<li>Báo cáo lương</li>
@endsection

<div class="page-head">
    <div>
        <h1>Báo cáo lương</h1>
        <p class="muted">Báo cáo tổng hợp lương</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('accountant.dashboard') }}">Quay lại</a>
    </div>
</div>

<div class="card">
    <div class="empty">Chưa có báo cáo nào. Vui lòng cấu hình bộ lọc và chạy báo cáo.</div>
</div>

@endsection
