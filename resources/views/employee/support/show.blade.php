@extends('layouts.app')

@section('title', 'Yêu cầu hỗ trợ')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('me.dashboard') }}">Dashboard</a></li>
<li><a href="{{ route('me.support_requests') }}">Yêu cầu hỗ trợ</a></li>
<li>Chi tiết</li>
@endsection
<div class="page-head">
    <div>
        <h1>{{ $supportRequest->subject }}</h1>
        <p class="muted">{{ ucfirst($supportRequest->type) }} • {{ $supportRequest->created_at->format('Y-m-d') }}</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('me.support_requests') }}">Quay lại</a>
    </div>
</div>

<div class="card">
    <div class="field"><label>Nội dung</label><div>{{ $supportRequest->message }}</div></div>
    <div class="field"><label>Trạng thái</label><div><span class="badge">{{ ucfirst($supportRequest->status) }}</span></div></div>
    @if($supportRequest->attachment)
        <div class="field"><label>Đính kèm</label><div><a href="{{ asset('storage/' . $supportRequest->attachment) }}" target="_blank">Tải về</a></div></div>
    @endif
</div>

@endsection
