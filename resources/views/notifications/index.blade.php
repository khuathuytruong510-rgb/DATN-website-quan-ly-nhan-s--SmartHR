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
        @if (auth()->user()->is_admin && ! auth()->user()->is_hr)
            <a href="{{ route('accounts.index') }}" class="btn">Quản lý tài khoản</a>
        @endif
    </div>

    <div class="card">
        @if (isset($notifications) && $notifications->isNotEmpty())
            <div class="list-group list-group-flush">
                @foreach ($notifications as $notification)
                    @php
                        $milestone = data_get($notification->data, 'milestone');
                        $urgentNotice = in_array($milestone, ['overdue', 'expired', '7'], true);
                    @endphp
                    <div class="list-group-item" style="{{ $urgentNotice ? 'border-left:4px solid #dc2626;' : '' }}">
                        <h4>{{ $notification->title }}</h4>
                        <p style="white-space:pre-wrap;">{{ $notification->message }}</p>
                        @php
                            $payrollId = data_get($notification->data, 'payroll_id');
                            if (! $payrollId && preg_match('/mã\s*#(\d+)/u', (string) $notification->message, $m)) {
                                $payrollId = (int) $m[1];
                            }
                            $isIssue = data_get($notification->data, 'type') === 'payroll_issue'
                                || str_contains((string) $notification->title, 'sự cố lương');
                            $isFace = data_get($notification->data, 'type') === 'face_registration';
                            $faceProfile = $isFace
                                ? \App\Models\FaceProfile::with('employee')->find(data_get($notification->data, 'face_profile_id'))
                                : null;
                            $leaveId = data_get($notification->data, 'leave_request_id');
                            $adjustmentId = data_get($notification->data, 'attendance_id');
                        @endphp
                        @if($isFace && $faceProfile)
                            <div style="margin:10px 0;">
                                @if($faceProfile->previewImage())
                                    <img src="{{ $faceProfile->previewImage() }}" alt="Ảnh khuôn mặt" style="width:120px;height:120px;object-fit:cover;border-radius:10px;border:1px solid #ddd;">
                                @endif
                                @if($faceProfile->isPending() && \App\Support\RequestApprover::canReview(auth()->user(), $faceProfile->employee))
                                    <div class="actions" style="margin-top:10px;">
                                        <form method="POST" action="{{ route('face_profiles.approve', $faceProfile) }}" style="display:inline">
                                            @csrf
                                            <button class="btn primary" type="submit">Duyệt khuôn mặt</button>
                                        </form>
                                        <form method="POST" action="{{ route('face_profiles.reject', $faceProfile) }}" style="display:inline">
                                            @csrf
                                            <input type="text" name="review_note" placeholder="Lý do từ chối" style="max-width:180px;">
                                            <button class="btn" type="submit">Từ chối</button>
                                        </form>
                                    </div>
                                @else
                                    <p class="muted">Đã xử lý ({{ $faceProfile->status }}).</p>
                                @endif
                            </div>
                        @endif
                        @if($leaveId && data_get($notification->data, 'type') === 'leave_request')
                            <div class="actions" style="margin-top:10px;">
                                <a href="{{ route('leave_requests.index') }}" class="btn primary">Mở đơn nghỉ phép</a>
                            </div>
                        @endif
                        @if(data_get($notification->data, 'type') === 'overtime_request')
                            <div class="actions" style="margin-top:10px;">
                                <a href="{{ route('leave_requests.index') }}" class="btn primary">Mở đăng ký tăng ca</a>
                            </div>
                        @endif
                        @if((data_get($notification->data, 'type') === 'support_request' || data_get($notification->data, 'type') === 'support_feedback') && data_get($notification->data, 'support_request_id') && (auth()->user()?->canManageHr() || auth()->user()?->is_director))
                            <div class="actions" style="margin-top:10px;">
                                <a href="{{ route('support_requests.show', data_get($notification->data, 'support_request_id')) }}" class="btn primary">{{ data_get($notification->data, 'type') === 'support_feedback' ? 'Xem phản hồi hỗ trợ' : 'Mở yêu cầu hỗ trợ' }}</a>
                            </div>
                        @endif
                        @if(data_get($notification->data, 'type') === 'attendance_adjustment')
                            <div class="actions" style="margin-top:10px;">
                                <a href="{{ route('attendance.index') }}" class="btn primary">Mở chấm công</a>
                            </div>
                        @endif
                        @if((data_get($notification->data, 'type') === 'deletion_request' || data_get($notification->data, 'type') === 'transfer_request' || data_get($notification->data, 'type') === 'transfer_feedback') && data_get($notification->data, 'deletion_request_id') && (auth()->user()->is_hr || auth()->user()->is_director))
                            <div class="actions" style="margin-top:10px;">
                                <a href="{{ route('deletion_requests.show', data_get($notification->data, 'deletion_request_id')) }}" class="btn primary">{{ data_get($notification->data, 'type') === 'transfer_feedback' ? 'Xem phản hồi điều chuyển' : (data_get($notification->data, 'type') === 'transfer_request' ? 'Mở yêu cầu chuyển' : 'Mở yêu cầu xóa') }}</a>
                            </div>
                        @endif
                        @if(data_get($notification->data, 'type') === 'account_deletion' && auth()->user()->is_admin)
                            <div class="actions" style="margin-top:10px;">
                                <a href="{{ route('accounts.index') }}" class="btn primary">Mở quản lý tài khoản</a>
                            </div>
                        @endif
                        @php
                            $contractNotice = in_array(data_get($notification->data, 'type'), ['contract_expiry', 'contract_expiry_decision', 'contract_renewal_result', 'contract_esign'], true);
                            $noticeContractId = data_get($notification->data, 'contract_id');
                        @endphp
                        @if($contractNotice && $noticeContractId)
                            <div class="actions" style="margin-top:10px;">
                                <a href="{{ route('contracts.show', $noticeContractId) }}" class="btn">Xem hợp đồng</a>
                                @if(auth()->user()?->canManageHr() && data_get($notification->data, 'type') === 'contract_expiry')
                                    <a href="{{ route('contracts.show', $noticeContractId) }}" class="btn primary">Xử lý hợp đồng</a>
                                @endif
                            </div>
                        @endif
                        @if($isIssue && ! auth()->user()->is_admin)
                            <div class="actions" style="margin-top:10px;">
                                @if($payrollId)
                                    <a href="{{ route('payroll.issues.fix_form', $payrollId) }}" class="btn primary">Khắc phục</a>
                                    <a href="{{ route('payroll.show', $payrollId) }}" class="btn">Xem phiếu</a>
                                @else
                                    <a href="{{ route('payroll.issues.index') }}" class="btn primary">Xem sự cố lương</a>
                                @endif
                            </div>
                        @endif
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
