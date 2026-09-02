@extends('layouts.app')

@section('title', 'Yêu cầu hỗ trợ')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('me.dashboard') }}">Dashboard</a></li>
<li>Yêu cầu hỗ trợ</li>
@endsection
<div class="page-head">
    <div>
        <h1>Yêu cầu hỗ trợ</h1>
        <p class="muted">Gửi yêu cầu cho {{ \App\Support\RequestApprover::queueLabel(auth()->user()?->linkedEmployee()) }} duyệt và xử lý. Khi xử lý xong bạn sẽ nhận thông báo và có thể phản hồi kết quả.</p>
    </div>
    <div class="actions">
        <a class="btn primary" href="{{ route('me.support_requests.create') }}">Tạo yêu cầu mới</a>
    </div>
</div>

<div class="card">
    @if($requests->isEmpty())
        <div class="empty">Chưa có yêu cầu hỗ trợ.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tiêu đề</th>
                    <th>Loại</th>
                    <th>Trạng thái</th>
                    <th>Ngày</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($requests as $r)
                <tr>
                    <td>{{ $r->id }}</td>
                    <td>{{ $r->subject }}</td>
                    <td>{{ $r->typeLabel() }}</td>
                    <td><span class="badge">{{ $r->statusLabel() }}</span></td>
                    <td>{{ $r->created_at->format('Y-m-d') }}</td>
                    <td><a class="btn" href="{{ route('me.support_requests.show', $r) }}">Xem</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="pagination">{{ $requests->links() }}</div>
    @endif
</div>

@endsection
