@extends('layouts.app')

@section('title', 'Quản lý thưởng')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('accountant.dashboard') }}">Kế toán</a></li>
<li>Quản lý thưởng</li>
@endsection

<div class="page-head">
    <div>
        <h1>Quản lý thưởng</h1>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('accountant.dashboard') }}">Quay lại</a>
    </div>
</div>

<div class="card">
    <div class="empty">Chưa có dữ liệu thưởng.</div>
</div>

@endsection
