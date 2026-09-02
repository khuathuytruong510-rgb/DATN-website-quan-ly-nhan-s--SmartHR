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

@if(session('success'))
    <div class="alert" style="background:#e6f4ea;border-left:4px solid #137333;padding:0.75rem 1rem;margin-bottom:1rem;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert" style="background:#ffebee;border-left:4px solid #f44336;padding:0.75rem 1rem;margin-bottom:1rem;">{{ session('error') }}</div>
@endif

<div class="card">
    <div class="field"><label>Nội dung</label><div style="white-space:pre-line;">{{ $supportRequest->message }}</div></div>
    <div class="field"><label>Trạng thái</label><div><span class="badge">{{ $supportRequest->statusLabel() }}</span></div></div>
    @if($supportRequest->follow_up)
        <div class="field"><label>Bổ sung của bạn</label><div style="white-space:pre-line;">{{ $supportRequest->follow_up }}</div></div>
    @endif
    @if($supportRequest->hr_reply)
        <div class="field"><label>Kết quả xử lý</label><div style="white-space:pre-line;">{{ $supportRequest->hr_reply }}</div></div>
    @endif
    @if($supportRequest->employee_feedback)
        <div class="field"><label>Phản hồi của bạn</label><div style="white-space:pre-line;">{{ $supportRequest->employee_feedback }}</div></div>
    @endif
    @if($supportRequest->attachment)
        <div class="field"><label>Đính kèm</label><div><a href="{{ asset('storage/' . $supportRequest->attachment) }}" target="_blank">Tải về</a></div></div>
    @endif
</div>

@if(in_array($supportRequest->status, ['pending', 'processing'], true))
<div class="card" style="margin-top:16px;">
    <h2>Bổ sung nội dung</h2>
    <p class="muted">Bạn không tự đóng yêu cầu hoặc đổi trạng thái. {{ \App\Support\RequestApprover::queueLabel($supportRequest->employee) }} sẽ duyệt rồi xử lý.</p>
    <form method="POST" action="{{ route('me.support_requests.follow_up', $supportRequest) }}">
        @csrf
        <div class="field">
            <textarea name="follow_up" rows="3" required></textarea>
        </div>
        <button class="btn primary" type="submit">Gửi bổ sung</button>
    </form>
</div>
@endif

@if($supportRequest->isResolved() && ! $supportRequest->employee_feedback)
<div class="card" style="margin-top:16px;">
    <h2>Phản hồi kết quả xử lý</h2>
    <p class="muted">Yêu cầu đã được xử lý xong. Gửi ý kiến về kết quả để {{ \App\Support\RequestApprover::queueLabel($supportRequest->employee) }} nắm thông tin.</p>
    <form method="POST" action="{{ route('me.support_requests.feedback', $supportRequest) }}">
        @csrf
        <div class="field">
            <textarea name="employee_feedback" rows="3" required placeholder="Ví dụ: đã ổn / chưa đúng / cần hỗ trợ thêm"></textarea>
        </div>
        <button class="btn primary" type="submit">Gửi phản hồi</button>
    </form>
</div>
@endif

@endsection
