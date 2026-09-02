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

    @if(session('success'))
        <div class="alert" style="background:#e6f4ea;border-left:4px solid #137333;padding:0.75rem 1rem;margin-bottom:1rem;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert" style="background:#ffebee;border-left:4px solid #f44336;padding:0.75rem 1rem;margin-bottom:1rem;">{{ session('error') }}</div>
    @endif
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
                                <p style="white-space:pre-wrap;">{{ $notification->message }}</p>
                                <small class="text-muted">{{ $notification->created_at->format('d/m/Y H:i') }}</small>
                                @if(data_get($notification->data, 'type') === 'transfer_notice' && data_get($notification->data, 'deletion_request_id'))
                                    @php
                                        $transfer = \App\Models\DeletionRequest::find(data_get($notification->data, 'deletion_request_id'));
                                        $me = auth()->user()?->linkedEmployee();
                                        $mine = $transfer && $me ? $transfer->feedbackFor($me->id) : null;
                                    @endphp
                                    @if($transfer && $me)
                                        @if($mine)
                                            <div style="margin-top:10px;padding:10px;background:#f8fafc;border-radius:8px;">
                                                <strong>Phản hồi của bạn:</strong> {{ ($mine['agree'] ?? false) ? 'Đã nắm thông tin' : 'Không đồng ý / có ý kiến' }}
                                                <div>{{ $mine['message'] ?? '' }}</div>
                                                @if(! empty($mine['hr_reply']))
                                                    <p style="margin:8px 0 0;"><strong>HR phản hồi:</strong> {{ $mine['hr_reply'] }}</p>
                                                @else
                                                    <p class="muted" style="margin:8px 0 0;">Đã gửi HR, đang chờ giải quyết.</p>
                                                @endif
                                            </div>
                                        @else
                                            <form method="POST" action="{{ route('me.transfers.feedback', $transfer) }}" style="margin-top:10px;">
                                                @csrf
                                                <div class="field">
                                                    <label><input type="radio" name="agree" value="1"> Đã nắm thông tin</label>
                                                    <label style="margin-left:12px;"><input type="radio" name="agree" value="0" checked> Không đồng ý / có ý kiến</label>
                                                </div>
                                                <div class="field">
                                                    <textarea name="message" rows="3" placeholder="Nêu ý kiến hoặc lý do không đồng ý để Nhân sự giải quyết"></textarea>
                                                </div>
                                                <button class="btn primary" type="submit">Gửi phản hồi cho HR</button>
                                            </form>
                                        @endif
                                    @endif
                                @endif
                                @if(in_array(data_get($notification->data, 'type'), ['contract_expiry', 'contract_renewal_result', 'contract_expiry_decision', 'contract_esign'], true) && data_get($notification->data, 'contract_id'))
                                    <div class="actions" style="margin-top:10px;">
                                        <a class="btn" href="{{ route('me.contracts.show', data_get($notification->data, 'contract_id')) }}">Xem hợp đồng</a>
                                    </div>
                                @endif
                                @if(in_array(data_get($notification->data, 'type'), ['support_request', 'support_resolved'], true) && data_get($notification->data, 'support_request_id'))
                                    <div class="actions" style="margin-top:10px;">
                                        <a class="btn" href="{{ route('me.support_requests.show', data_get($notification->data, 'support_request_id')) }}">Xem yêu cầu hỗ trợ</a>
                                    </div>
                                @endif
                                @if(data_get($notification->data, 'type') === 'support_resolved' && data_get($notification->data, 'support_request_id'))
                                    @php
                                        $ticket = \App\Models\SupportRequest::with('employee.user')->find(data_get($notification->data, 'support_request_id'));
                                        $me = auth()->user()?->linkedEmployee();
                                    @endphp
                                    @if($ticket && $me && (int) $ticket->employee_id === (int) $me->id)
                                        @if($ticket->employee_feedback)
                                            <div style="margin-top:10px;padding:10px;background:#f8fafc;border-radius:8px;">
                                                <strong>Phản hồi của bạn:</strong>
                                                <div>{{ $ticket->employee_feedback }}</div>
                                            </div>
                                        @else
                                            <form method="POST" action="{{ route('me.support_requests.feedback', $ticket) }}" style="margin-top:10px;">
                                                @csrf
                                                <div class="field">
                                                    <textarea name="employee_feedback" rows="3" required placeholder="Phản hồi về kết quả đã xử lý"></textarea>
                                                </div>
                                                <button class="btn primary" type="submit">Gửi phản hồi cho {{ \App\Support\RequestApprover::queueLabel($ticket->employee) }}</button>
                                            </form>
                                        @endif
                                    @endif
                                @endif
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
