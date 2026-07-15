@extends('layouts.app')

@section('title', 'Quản lý phụ cấp')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('accountant.dashboard') }}">Kế toán</a></li>
<li>Quản lý phụ cấp</li>
@endsection

<div class="page-head">
    <div>
        <h1>Quản lý phụ cấp</h1>
        <p class="muted">Danh mục phụ cấp</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('accountant.dashboard') }}">Quay lại</a>
    </div>
</div>

<div class="card">
    <div class="empty">Chưa có phụ cấp nào. Vui lòng thêm dữ liệu mẫu.</div>
</div>

@endsection
