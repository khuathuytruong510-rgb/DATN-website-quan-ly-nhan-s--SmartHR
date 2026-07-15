@extends('layouts.app')

@section('title', 'Phản hồi lương')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('accountant.dashboard') }}">Kế toán</a></li>
<li>Phản hồi lương</li>
@endsection

<div class="page-head">
    <div>
        <h1>Phản hồi lương</h1>
        <p class="muted">Danh sách phản hồi từ nhân viên</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('accountant.payroll.index') }}">Quay lại</a>
    </div>
</div>

<div class="card">
    <div class="empty">Chưa có phản hồi nào.</div>
</div>

@endsection
