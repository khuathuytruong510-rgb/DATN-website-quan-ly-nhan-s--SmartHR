@extends('layouts.app')

@section('title', 'Thông báo')

@section('content')
    <div class="page-head">
        <div>
            <h1>Thông báo</h1>
            <p class="muted">Các thông báo hiện tại sẽ xuất hiện tại đây.</p>
        </div>
        @if (auth()->user()->is_admin || auth()->user()->is_hr)
            <a href="{{ route('notifications.create') }}" class="btn primary">Tạo thông báo</a>
        @endif
    </div>

    <div class="card">
        @if (isset($notifications) && $notifications->isNotEmpty())
            <div class="list-group list-group-flush">
                @foreach ($notifications as $notification)
                    <div class="list-group-item">
                        <h4>{{ $notification->title }}</h4>
                        <p>{{ $notification->message }}</p>
                        <p class="muted">Gửi đến: {{ ucfirst($notification->target) }} | {{ $notification->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                @endforeach
            </div>
            <div class="card-footer">
                {{ $notifications->links() }}
            </div>
        @else
            <div class="empty">Chưa có thông báo nào.</div>
        @endif
    </div>
@endsection
