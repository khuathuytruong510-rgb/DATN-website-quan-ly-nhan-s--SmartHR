@extends('layouts.app')

@section('title', 'Yêu cầu hỗ trợ')

@section('content')
<div class="page-head">
    <div>
        <h1>Yêu cầu hỗ trợ nhân viên</h1>
        <p class="muted">Nhân viên → HR duyệt và xử lý. Yêu cầu của HR → Giám đốc duyệt và xử lý. HR không quản lý hồ sơ Giám đốc.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert" style="background:#e6f4ea;border-left:4px solid #137333;padding:0.75rem 1rem;margin-bottom:1rem;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert" style="background:#ffebee;border-left:4px solid #f44336;padding:0.75rem 1rem;margin-bottom:1rem;">{{ session('error') }}</div>
@endif

<div class="actions" style="margin-bottom:1rem;gap:8px;">
    <a class="btn {{ $status === null ? 'primary' : '' }}" href="{{ route('support_requests.index') }}">Tất cả</a>
    <a class="btn {{ $status === 'pending' ? 'primary' : '' }}" href="{{ route('support_requests.index', ['status' => 'pending']) }}">Chờ duyệt</a>
    <a class="btn {{ $status === 'processing' ? 'primary' : '' }}" href="{{ route('support_requests.index', ['status' => 'processing']) }}">Đã duyệt</a>
    <a class="btn {{ $status === 'resolved' ? 'primary' : '' }}" href="{{ route('support_requests.index', ['status' => 'resolved']) }}">Đã xử lý</a>
</div>

<div class="card" style="padding:0;overflow:hidden;">
    <table>
        <thead>
            <tr>
                <th>Nhân viên</th>
                <th>Tiêu đề</th>
                <th>Loại</th>
                <th>Trạng thái</th>
                <th>Ngày</th>
                <th style="text-align:right;">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($requests as $ticket)
                <tr>
                    <td>{{ optional($ticket->employee)->name ?? '—' }}</td>
                    <td>
                        {{ $ticket->subject }}
                        @if($ticket->isResolved() && $ticket->employee_feedback)
                            <div class="muted" style="font-size:13px;color:#c2410c;">Có phản hồi từ nhân viên</div>
                        @endif
                    </td>
                    <td>{{ $ticket->typeLabel() }}</td>
                    <td>{{ $ticket->statusLabel() }}</td>
                    <td>{{ optional($ticket->created_at)->format('d/m/Y H:i') }}</td>
                    <td>
                        <div class="actions" style="justify-content:flex-end;">
                            <a class="btn" href="{{ route('support_requests.show', $ticket) }}">Xem</a>
                            @if(\App\Support\RequestApprover::canReview($reviewer, $ticket->employee) && $ticket->isPending())
                                <form method="POST" action="{{ route('support_requests.approve', $ticket) }}">
                                    @csrf
                                    <button class="btn primary" type="submit">Duyệt</button>
                                </form>
                            @elseif(\App\Support\RequestApprover::canReview($reviewer, $ticket->employee) && $ticket->isProcessing())
                                <form method="POST" action="{{ route('support_requests.resolve', $ticket) }}">
                                    @csrf
                                    <button class="btn primary" type="submit">Đã xử lý</button>
                                </form>
                            @elseif($ticket->isResolved())
                                <span class="muted">Đã xử lý</span>
                            @elseif(\App\Support\RequestApprover::needsDirector($ticket->employee))
                                <span class="muted">Chờ Giám đốc</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6"><div class="empty">Chưa có yêu cầu hỗ trợ.</div></td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="pagination" style="padding:12px;">{{ $requests->links() }}</div>
</div>
@endsection
