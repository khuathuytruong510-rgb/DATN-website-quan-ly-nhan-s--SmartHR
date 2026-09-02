@extends('layouts.app')

@section('title', 'Đơn nghỉ phép của tôi')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('me.dashboard') }}">Dashboard</a></li>
<li>Đơn xin nghỉ</li>
@endsection
    <div class="page-head">
        <div>
            <h1>Đơn nghỉ phép của tôi</h1>
            <p class="muted">Tạo đơn và theo dõi trạng thái. {{ \App\Support\RequestApprover::queueLabel($employee) }} duyệt/từ chối; đơn đã duyệt được dùng khi kế toán tính lương.</p>
        </div>
        <a class="btn primary" href="{{ route('me.leave_requests.create') }}">Tạo đơn mới</a>
    </div>

    @if(isset($leaveLimit))
        <div class="alert" style="margin-bottom: 1rem; padding: 0.75rem 1rem; border-radius: 4px; {{ $leaveLimit['remaining_days'] > 0 ? 'background: #e8f5e9; border-left: 4px solid #4CAF50;' : 'background: #ffebee; border-left: 4px solid #f44336;' }}">
            <strong>Hạn mức theo hợp đồng:</strong>
            Phép năm <strong>{{ $leaveLimit['annual_used'] ?? $leaveLimit['used_days'] }}/{{ $leaveLimit['annual_max'] ?? $leaveLimit['max_days'] }}</strong> ngày
            (còn {{ $leaveLimit['annual_remaining'] ?? $leaveLimit['remaining_days'] }}).
            Không lương/việc riêng: {{ $leaveLimit['unpaid_used'] ?? 0 }}/{{ $leaveLimit['unpaid_max'] ?? 1 }} ngày tháng này.
            @if(isset($employee) && $employee->isFemale())
                Thai sản: {{ $leaveLimit['maternity_used'] ?? 0 }}/{{ $leaveLimit['maternity_max'] ?? 180 }} ngày.
            @endif
            @if(($leaveLimit['remaining_days'] ?? 0) <= 0)
                <span style="color: #d32f2f;">Đã hết phép năm. Chọn loại nghỉ khác nếu hợp đồng còn hạn mức.</span>
            @endif
        </div>
    @endif

    @if($leaves->isEmpty())
        <div class="empty">
            <p>Chưa có đơn nghỉ phép.</p>
            <p>Bạn có thể tạo đơn nghỉ phép mới để gửi tới bộ phận nhân sự.</p>
        </div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Ngày nghỉ</th>
                    <th>Số ngày</th>
                    <th>Loại nghỉ</th>
                    <th>Lý do</th>
                    <th>Trạng thái</th>
                    <th>Người duyệt</th>
                    <th>Ngày gửi</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($leaves as $leave)
                    <tr>
                        <td>#{{ $leave->id }}</td>
                        <td>{{ \Carbon\Carbon::parse($leave->start_date)->format('d/m/Y') }} → {{ \Carbon\Carbon::parse($leave->end_date)->format('d/m/Y') }}</td>
                        <td>
                            @if($leave->half_day)
                                <span style="color: #1976d2; font-weight: bold;">{{ $leave->days }} (1/2 ngày)</span>
                            @else
                                {{ $leave->days }}
                            @endif
                        </td>
                        <td>{{ $leave->type_label }}</td>
                        <td>{{ $leave->reason ?? '-' }}@if($leave->is_urgent) <span style="color:#d32f2f;">(Khẩn cấp)</span>@endif</td>
                        <td>
                            <span class="badge {{ $leave->status }}">
                                {{ match($leave->status) {
                                    'approved' => 'Đã duyệt',
                                    'rejected' => 'Từ chối',
                                    'cancelled' => 'Đã hủy',
                                    default => 'Chờ duyệt',
                                } }}
                            </span>
                        </td>
                        <td>{{ optional($leave->approver)->name ?? '—' }}</td>
                        <td>{{ optional($leave->created_at)->format('d/m/Y H:i') }}</td>
                        <td>
                            @if(($leave->status ?: 'pending') === 'pending')
                                <form method="POST" action="{{ route('me.leave_requests.cancel', $leave) }}" onsubmit="return confirm('Hủy đơn này? Lịch sử đơn vẫn được giữ.')">
                                    @csrf
                                    <button class="btn" type="submit">Hủy đơn</button>
                                </form>
                            @elseif($leave->status === 'approved' && optional($leave->start_date)->toDateString() > now()->toDateString())
                                <form method="POST" action="{{ route('me.leave_requests.cancel', $leave) }}" onsubmit="return confirm('Hủy đơn đã duyệt trước ngày nghỉ?')">
                                    @csrf
                                    <input type="text" name="cancel_reason" required placeholder="Lý do hủy" style="max-width:160px;">
                                    <button class="btn" type="submit">Hủy đơn</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
