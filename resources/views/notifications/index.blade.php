@extends('layouts.app')

@section('title', 'Thông báo')

@section('content')
    <div class="page-head">
        <div>
            <h1>Thông báo</h1>
            <p class="muted">Các thông báo hiện tại sẽ xuất hiện tại đây.</p>
        </div>
        @if (auth()->user()->is_hr || auth()->user()->is_director)
            <a href="{{ route('notifications.create') }}" class="btn primary">Tạo thông báo</a>
        @endif
    </div>

    <div class="card">
        @if (isset($notifications) && $notifications->isNotEmpty())
            <div class="list-group list-group-flush">
                @foreach ($notifications as $notification)
                    <div class="list-group-item">
                        <h4>{{ $notification->title }}</h4>
                        <p style="white-space:pre-wrap;">{{ $notification->message }}</p>
                        <p class="muted">Gửi đến: {{ ucfirst($notification->target) }} | {{ $notification->created_at->format('d/m/Y H:i') }}</p>
                        @php
                            $payrollId = data_get($notification->data, 'payroll_id');
                            if (! $payrollId && preg_match('/mã\s*#(\d+)/u', (string) $notification->message, $m)) {
                                $payrollId = (int) $m[1];
                            }
                            $isIssue = data_get($notification->data, 'type') === 'payroll_issue'
                                || str_contains((string) $notification->title, 'sự cố lương');
                        @endphp
                        @if($isIssue)
                            <div class="actions" style="margin-top:10px;">
                                @if($payrollId)
                                    <a href="{{ route('payroll.issues.fix_form', $payrollId) }}" class="btn primary">Khắc phục</a>
                                    <a href="{{ route('payroll.show', $payrollId) }}" class="btn">Xem phiếu</a>
                                @else
                                    <a href="{{ route('payroll.issues.index') }}" class="btn primary">Xem sự cố lương</a>
                                @endif
                            </div>
                        @endif
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
