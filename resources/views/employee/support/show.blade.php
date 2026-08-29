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
        <p class="muted">{{ $supportRequest->typeLabel() }} • {{ $supportRequest->created_at->format('d/m/Y') }}</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('me.support_requests') }}">Quay lại</a>
    </div>
</div>

<div class="card">
    <div class="field"><label>Nội dung</label><div>{{ $supportRequest->message }}</div></div>
    <div class="field"><label>Trạng thái</label><div><span class="badge">{{ $supportRequest->statusLabel() }}</span></div></div>
    @if($supportRequest->follow_up)
        <div class="field"><label>Bổ sung của bạn</label><div style="white-space:pre-line;">{{ $supportRequest->follow_up }}</div></div>
    @endif
    @if($supportRequest->hr_reply)
        <div class="field"><label>Phản hồi</label><div>{{ $supportRequest->hr_reply }}</div></div>
    @endif
    @if($supportRequest->attachment)
        <div class="field"><label>Đính kèm</label><div><a href="{{ asset('storage/' . $supportRequest->attachment) }}" target="_blank">Tải về</a></div></div>
    @endif
</div>

@if(in_array($supportRequest->status, ['pending', 'processing'], true))
<div class="card" style="margin-top:16px;">
    <h2>Bổ sung nội dung</h2>
    <p class="muted">Bạn không tự đóng yêu cầu hoặc đổi trạng thái.</p>
    <form method="POST" action="{{ route('me.support_requests.follow_up', $supportRequest) }}">
        @csrf
        <div class="field">
            <textarea name="follow_up" rows="3" required></textarea>
        </div>
        <button class="btn primary" type="submit">Gửi bổ sung</button>
    </form>
</div>
@endif

@endsection
