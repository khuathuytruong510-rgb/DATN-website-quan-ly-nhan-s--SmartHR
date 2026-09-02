@extends('layouts.app')

@section('title', 'Quản lý khấu trừ')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('accountant.dashboard') }}">Kế toán</a></li>
<li>Quản lý khấu trừ</li>
@endsection

<div class="page-head">
    <div>
        <h1>Quản lý khấu trừ</h1>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('accountant.dashboard') }}">Quay lại</a>
    </div>
</div>

<div class="card">
    <div class="empty">Chưa có khấu trừ nào.</div>
</div>

@endsection
