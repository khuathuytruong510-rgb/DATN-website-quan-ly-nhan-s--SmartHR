@extends('layouts.app')

@section('title', 'Tính lương')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('accountant.dashboard') }}">Kế toán</a></li>
<li>Tính lương</li>
@endsection

<div class="page-head">
    <div>
        <h1>Tính lương tự động</h1>
        <p class="muted">Tạo bảng lương cho toàn bộ nhân viên đang hoạt động cho tháng được chọn.</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('accountant.payroll.index') }}">Quay lại danh sách</a>
    </div>
</div>

@if(session('success'))
    <div class="alert">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert" style="background:#fee2e2;color:#dc2626;">{{ session('error') }}</div>
@endif

<div class="card">
    <form method="POST" action="{{ route('accountant.payroll.generate_post') }}">
        @csrf
        <div class="field">
            <label for="month">Chọn tháng</label>
            <input id="month" name="month" type="month" value="{{ now()->format('Y-m') }}" required>
        </div>
        <button class="btn primary" type="submit">Tính lương tự động</button>
    </form>
</div>

@endsection
