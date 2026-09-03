@extends('layouts.app')

@section('title', 'Chi tiết yêu cầu hỗ trợ')

@section('content')
<div class="page-head">
    <div>
        <h1>{{ $ticket->subject }}</h1>
    </div>
    <a class="btn link" href="{{ route('support_requests.index') }}">Danh sách</a>
</div>

@if(session('success'))
    <div class="alert" style="background:#e6f4ea;border-left:4px solid #137333;padding:0.75rem 1rem;margin-bottom:1rem;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert" style="background:#ffebee;border-left:4px solid #f44336;padding:0.75rem 1rem;margin-bottom:1rem;">{{ session('error') }}</div>
@endif

<div class="card">
    <div class="emp-dl">
        <div><label>Nhân viên</label><div>{{ optional($ticket->employee)->name }} ({{ optional($ticket->employee)->email }})</div></div>
        <div><label>Loại</label><div>{{ $ticket->typeLabel() }}</div></div>
        <div><label>Trạng thái</label><div>{{ $ticket->statusLabel() }}</div></div>
        <div><label>Nội dung</label><div style="white-space:pre-line;">{{ $ticket->message }}</div></div>
        @if($ticket->follow_up)
            <div><label>Bổ sung của nhân viên</label><div style="white-space:pre-line;">{{ $ticket->follow_up }}</div></div>
        @endif
        @if($ticket->hr_reply)
            <div><label>Kết quả xử lý</label><div style="white-space:pre-line;">{{ $ticket->hr_reply }}</div></div>
        @endif
        @if($ticket->employee_feedback)
            <div><label>Phản hồi nhân viên</label><div style="white-space:pre-line;">{{ $ticket->employee_feedback }}</div></div>
        @endif
        @if($ticket->attachment)
            <div><label>Đính kèm</label><div><a href="{{ asset('storage/'.$ticket->attachment) }}" target="_blank">Tải về</a></div></div>
        @endif
    </div>

    @if($canReview && $ticket->isPending())
        <form method="POST" action="{{ route('support_requests.approve', $ticket) }}" style="margin-top:16px;">
            @csrf
            <button class="btn primary" type="submit">Duyệt</button>
        </form>
    @elseif($canReview && $ticket->isProcessing())
        <form method="POST" action="{{ route('support_requests.resolve', $ticket) }}" style="margin-top:16px;">
            @csrf
            <div class="field">
                <label>Kết quả xử lý (gửi cho người yêu cầu)</label>
                <textarea name="hr_reply" rows="3" placeholder="Mô tả cách đã xử lý">{{ old('hr_reply', $ticket->hr_reply) }}</textarea>
            </div>
            <button class="btn primary" type="submit">Đã xử lý</button>
        </form>
    @elseif($ticket->isResolved())
        <p class="muted" style="margin-top:16px;">Đã xử lý.{{ $ticket->employee_feedback ? '' : ' Đang chờ phản hồi.' }}</p>
    @elseif(\App\Support\RequestApprover::needsDirector($ticket->employee))
        <p class="muted" style="margin-top:16px;">Chờ Giám đốc duyệt và xử lý.</p>
    @endif
</div>
@endsection
