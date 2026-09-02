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
        <button class="btn primary" type="submit">Gửi email xác nhận</button>
    </form>
</div>

@endsection
