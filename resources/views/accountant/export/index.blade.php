@extends('layouts.app')

@section('title', 'Xuất PDF/Excel')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('accountant.dashboard') }}">Kế toán</a></li>
<li>Xuất PDF/Excel</li>
@endsection

<div class="page-head">
    <div>
        <h1>Xuất PDF/Excel</h1>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('accountant.dashboard') }}">Quay lại</a>
    </div>
</div>

<div class="card">
    <div class="empty">Chưa có bản xuất. Tính năng đang được triển khai.</div>
</div>

@endsection
