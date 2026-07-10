@extends('layouts.app')

@section('title', 'Đơn nghỉ phép của tôi')

@section('content')
    <div class="page-head">
        <div>
            <h1>Đơn nghỉ phép của tôi</h1>
            <p class="muted">Tạo và xem trạng thái các đơn xin nghỉ của bạn.</p>
        </div>
        <a class="btn primary" href="{{ route('me.leave_requests.create') }}">Tạo đơn mới</a>
    </div>

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
                </tr>
            </thead>
            <tbody>
                @foreach($leaves as $leave)
                    <tr>
                        <td>{{ optional($leave->start_date)->format('d/m/Y') }}</td>
                        <td>{{ optional($leave->end_date)->format('d/m/Y') }}</td>
                        <td>{{ $leave->days }}</td>
                        <td>{{ ucfirst($leave->type) }}</td>
                        <td><span class="badge {{ $leave->status }}">{{ ucfirst($leave->status) }}</span></td>
                        <td>{{ $leave->reason ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
