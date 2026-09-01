@extends('layouts.app')

@section('title', 'Thông báo của tôi')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('me.dashboard') }}">Dashboard</a></li>
<li>Thông báo</li>
@endsection
    <div class="page-head">
        <div>
            <h1>Thông báo</h1>
            <p class="muted">Thông báo dành cho bạn. Có thể đánh dấu đã đọc, không sửa hoặc xóa thông báo hệ thống.</p>
        </div>
    </div>

    <div class="card">
        @if ($notifications->isEmpty())
            <div class="empty">Chưa có thông báo nào cho bạn.</div>
        @else
            <div class="list-group list-group-flush">
                @foreach ($notifications as $notification)
                    @php $isRead = $notification->isReadBy(auth()->id()); @endphp
                    <div class="list-group-item" style="{{ $isRead ? '' : 'background:#f8fafc;' }}">
                        <div class="d-flex justify-content-between gap-3">
                            <div>
                                <h5>{{ $notification->title }} @if(!$isRead)<span class="badge">Chưa đọc</span>@endif</h5>
                                <p>{{ $notification->message }}</p>
                                <small class="text-muted">{{ $notification->created_at->format('d/m/Y H:i') }}</small>
                            </div>
                            @if(! $isRead)
                                <form method="POST" action="{{ route('me.notifications.read', $notification) }}">
                                    @csrf
                                    <button class="btn" type="submit">Đánh dấu đã đọc</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="card-footer">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
@endsection
