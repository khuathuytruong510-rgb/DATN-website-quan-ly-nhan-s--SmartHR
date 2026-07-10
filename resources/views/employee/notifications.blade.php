@extends('layouts.app')

@section('title', 'Thông báo của tôi')

@section('content')
    <div class="page-head">
        <div>
            <h1>Thông báo</h1>
            <p class="muted">Thông báo dành cho nhân viên sẽ hiển thị tại đây.</p>
        </div>
    </div>

    <div class="card">
        @if ($notifications->isEmpty())
            <div class="empty">Chưa có thông báo nào cho bạn.</div>
        @else
            <div class="list-group list-group-flush">
                @foreach ($notifications as $notification)
                    <div class="list-group-item">
                        <h5>{{ $notification->title }}</h5>
                        <p>{{ $notification->message }}</p>
                        <small class="text-muted">{{ $notification->created_at->format('d/m/Y H:i') }}</small>
                    </div>
                @endforeach
            </div>
            <div class="card-footer">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
@endsection
