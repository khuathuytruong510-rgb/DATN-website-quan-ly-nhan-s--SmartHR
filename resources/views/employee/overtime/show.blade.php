@extends('layouts.app')

@section('title', 'Chi tiết yêu cầu tăng ca')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('me.dashboard') }}">Dashboard</a></li>
<li><a href="{{ route('me.overtime_requests') }}">Đăng ký tăng ca</a></li>
<li>Chi tiết</li>
@endsection
    <div class="page-head">
        <div>
            <h1>Chi tiết yêu cầu tăng ca</h1>
            <p class="muted">Khung được duyệt là khoảng được phép tính OT. Số giờ thực tế do hệ thống tính từ checkout, không sửa được.</p>
        </div>
    </div>

    <div class="card">
        <div class="field"><label>Nguồn</label><div>{{ $overtimeRequest->sourceLabel() }}</div></div>
        <div class="field"><label>Ngày</label><div>{{ optional($overtimeRequest->date)->format('d/m/Y') }}</div></div>
        <div class="field"><label>Thời gian đăng ký</label><div>{{ $overtimeRequest->requestedWindowLabel() }}</div></div>
        <div class="field"><label>Thời gian được duyệt</label><div>{{ $overtimeRequest->approvedWindowLabel() }}</div></div>
        <div class="field"><label>Thời gian thực tế</label><div>{{ $overtimeRequest->actualWindowLabel() }}</div></div>
        <div class="field"><label>Lý do</label><div>{{ $overtimeRequest->reason ?? '-' }}</div></div>
        <div class="field"><label>Trạng thái</label><div>{{ $overtimeRequest->statusLabel() }}</div></div>
        @if($overtimeRequest->rejection_reason)
            <div class="field"><label>Lý do từ chối</label><div>{{ $overtimeRequest->rejection_reason }}</div></div>
        @endif
    </div>
@endsection
