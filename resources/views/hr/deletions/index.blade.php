@extends('layouts.app')

@section('title', 'Nghỉ việc / điều chuyển')

@section('content')
<div class="page-head">
    <div>
        <h1>{{ $canReview ? 'Duyệt nghỉ việc / điều chuyển' : 'Lịch sử nghỉ việc / điều chuyển' }}</h1>
    </div>
</div>

@if(session('success'))
    <div class="alert" style="background:#e6f4ea;border-left:4px solid #137333;padding:0.75rem 1rem;margin-bottom:1rem;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert" style="background:#ffebee;border-left:4px solid #f44336;padding:0.75rem 1rem;margin-bottom:1rem;">{{ session('error') }}</div>
@endif

<div class="actions" style="margin-bottom:1rem;gap:8px;">
    <a class="btn {{ $status === null ? 'primary' : '' }}" href="{{ route('deletion_requests.index') }}">Tất cả</a>
    <a class="btn {{ $status === 'pending' ? 'primary' : '' }}" href="{{ route('deletion_requests.index', ['status' => 'pending']) }}">Chờ duyệt</a>
    <a class="btn {{ $status === 'approved' ? 'primary' : '' }}" href="{{ route('deletion_requests.index', ['status' => 'approved']) }}">Đã duyệt</a>
    <a class="btn {{ $status === 'rejected' ? 'primary' : '' }}" href="{{ route('deletion_requests.index', ['status' => 'rejected']) }}">Từ chối</a>
</div>

<div class="card" style="padding:0;overflow:hidden;">
    <table>
        <thead>
            <tr>
                <th>Đối tượng</th>
                <th>Lý do / tài liệu</th>
                <th>Người gửi</th>
                <th>Trạng thái</th>
                <th style="text-align:right;">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($requests as $req)
                <tr>
                    <td>
                        <strong>{{ $req->typeLabel() }}</strong>
                        <div>{{ $req->subject_label }}</div>
                        @if($req->isTransfer() && $req->pendingFeedbackCount() > 0)
                            <div class="muted" style="font-size:13px;color:#c2410c;">{{ $req->pendingFeedbackCount() }} phản hồi chờ HR giải quyết</div>
                        @endif
                        @if($req->status === 'approved' && $req->account_email && ! $req->account_cleared_at)
                            <div class="muted" style="font-size:13px;">Tài khoản {{ $req->account_email }} đã khóa đăng nhập</div>
                        @endif
                    </td>
                    <td>
                        <div>{{ \Illuminate\Support\Str::limit($req->reason, 80) ?: '—' }}</div>
                        @if($req->document_path)
                            <a href="{{ route('deletion_requests.document', $req) }}">{{ $req->document_name ?: 'Tải biên bản' }}</a>
                        @endif
                    </td>
                    <td>
                        {{ optional($req->requester)->name }}
                        <div class="muted" style="font-size:13px;">{{ optional($req->created_at)->format('d/m/Y H:i') }}</div>
                    </td>
                    <td>{{ $req->statusLabel() }}</td>
                    <td>
                        <div class="actions" style="justify-content:flex-end;">
                            <a class="btn" href="{{ route('deletion_requests.show', $req) }}">Xem</a>
                            @if($canReview && $req->isPending())
                                <form method="POST" action="{{ route('deletion_requests.approve', $req) }}">
                                    @csrf
                                    <button class="btn primary" type="submit" data-confirm="{{ $req->isTransfer() ? 'Duyệt và chuyển nhân viên ngay?' : ($req->isEmployee() ? 'Duyệt nghỉ việc? Hồ sơ được giữ lại, hợp đồng chấm dứt, tài khoản khóa đăng nhập.' : 'Duyệt và xóa phòng ban? Dữ liệu sẽ được lưu vào lịch sử.') }}">{{ $req->approveActionLabel() }}</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5"><div class="empty">Chưa có yêu cầu.</div></td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($requests->hasPages())
    <div class="pagination">{{ $requests->links() }}</div>
@endif
@endsection
