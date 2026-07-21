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
            <p class="muted">Tạo và xem trạng thái các đơn xin nghỉ của bạn.</p>
        </div>
        <a class="btn primary" href="{{ route('me.leave_requests.create') }}">Tạo đơn mới</a>
    </div>

    @if(isset($leaveLimit))
        <div class="alert" style="margin-bottom: 1rem; padding: 0.75rem 1rem; border-radius: 4px; {{ $leaveLimit['remaining_days'] > 0 ? 'background: #e8f5e9; border-left: 4px solid #4CAF50;' : 'background: #ffebee; border-left: 4px solid #f44336;' }}">
            <strong>Quy định nghỉ phép tháng này:</strong>
            Đã sử dụng <strong>{{ $leaveLimit['used_days'] }}/{{ $leaveLimit['max_days'] }}</strong> ngày
            (<strong>{{ $leaveLimit['used_requests'] }}/{{ $leaveLimit['max_requests'] }}</strong> lượt).
            @if($leaveLimit['remaining_days'] > 0)
                Còn lại <strong>{{ $leaveLimit['remaining_days'] }}</strong> ngày phép.
            @else
                <span style="color: #d32f2f;">Bạn đã hết ngày phép trong tháng. Nếu cần nghỉ thêm, vui lòng chọn mục "Khẩn cấp" và cung cấp lý do thuyết phục.</span>
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
                    <th>Ngày bắt đầu</th>
                    <th>Ngày kết thúc</th>
                    <th>Số ngày</th>
                    <th>Loại</th>
                    <th>Trạng thái</th>
                    <th>Lý do</th>
                    <th>Khẩn cấp</th>
                </tr>
            </thead>
            <tbody>
                @foreach($leaves as $leave)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($leave->start_date)->format('d/m/Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($leave->end_date)->format('d/m/Y') }}</td>
                        <td>
                            @if($leave->half_day)
                                <span style="color: #1976d2; font-weight: bold;">{{ $leave->days }} (1/2 ngày)</span>
                            @else
                                {{ $leave->days }}
                            @endif
                        </td>
                        <td>{{ ucfirst($leave->type) }}</td>
                        <td><span class="badge {{ $leave->status }}">{{ ucfirst($leave->status) }}</span></td>
                        <td>{{ $leave->reason ?? '-' }}</td>
                        <td>
                            @if($leave->is_urgent)
                                <span style="color: #d32f2f; font-weight: bold;">Khẩn cấp</span>
                                @if($leave->urgent_reason)
                                    <br><small style="color: #666;">{{ Str::limit($leave->urgent_reason, 50) }}</small>
                                @endif
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
