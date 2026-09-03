@extends('layouts.app')

@section('title', 'Duyệt đổi STK/QR')

@section('content')
<div class="content" style="max-width:1000px;">
    <div class="page-head">
        <div>
            <h1>Yêu cầu đổi thông tin nhận lương</h1>
        </div>
    </div>

    <div class="card" style="padding:0;overflow:hidden;">
        <table>
            <thead>
                <tr>
                    <th>Nhân viên</th>
                    <th>Thông tin mới</th>
                    <th>Ghi chú</th>
                    <th>Trạng thái</th>
                    <th style="text-align:right;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                    <tr>
                        <td>
                            <strong>{{ optional($req->employee)->name }}</strong>
                            <div class="muted" style="font-size:13px;">{{ optional($req->employee)->email }}</div>
                        </td>
                        <td>
                            <div>{{ $req->bank_name ?: '—' }}</div>
                            <div>{{ $req->account_number ?: '—' }}</div>
                            <div>{{ $req->account_holder ?: '—' }}</div>
                            @if($req->qr_image)
                                <img src="{{ asset('storage/'.$req->qr_image) }}" alt="QR" style="max-width:80px;margin-top:6px;border-radius:6px;">
                            @endif
                        </td>
                        <td>{{ $req->note ?: '—' }}</td>
                        <td>
                            @if($req->status === 'pending')
                                <span class="badge pending">Chờ duyệt</span>
                            @elseif($req->status === 'approved')
                                <span class="badge" style="background:#dcfce7;color:#166534;">Đã duyệt</span>
                            @else
                                <span class="badge" style="background:#fee2e2;color:#991b1b;">Từ chối</span>
                            @endif
                        </td>
                        <td>
                            @if($req->status === 'pending')
                                <div class="actions" style="justify-content:flex-end;">
                                    @if(\App\Support\RequestApprover::canReview(auth()->user(), $req->employee))
                                    <form method="POST" action="{{ route('payroll.bank_requests.approve', $req) }}">
                                        @csrf
                                        <button class="btn primary" type="submit">Duyệt</button>
                                    </form>
                                    <form method="POST" action="{{ route('payroll.bank_requests.reject', $req) }}">
                                        @csrf
                                        <input type="hidden" name="review_note" value="Từ chối yêu cầu">
                                        <button class="btn danger" type="submit" data-confirm="Từ chối yêu cầu này?" data-confirm-variant="danger">Từ chối</button>
                                    </form>
                                    @elseif(\App\Support\RequestApprover::needsDirector($req->employee))
                                        <span class="muted">Chờ Giám đốc duyệt</span>
                                    @endif
                                </div>
                            @else
                                <span class="muted">{{ optional($req->reviewer)->name }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5"><div class="empty">Chưa có yêu cầu nào.</div></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:16px;">{{ $requests->links() }}</div>
</div>
@endsection
