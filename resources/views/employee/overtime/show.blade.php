@extends('layouts.app')

@section('title', 'Chi tiết yêu cầu tăng ca')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('me.dashboard') }}">Dashboard</a></li>
<li><a href="{{ route('me.overtime_requests') }}">Đăng ký tăng ca</a></li>
<li>Chi tiết</li>
@endsection
    <div class="page-head">
        <div>
            <h1>Chi tiết yêu cầu tăng ca</h1>
            <p class="muted">Xem chi tiết yêu cầu của bạn.</p>
        </div>
    </div>

    <div class="card">
        <div class="field"><label>Ngày</label><div>{{ optional($overtimeRequest->date)->format('d/m/Y') }}</div></div>
        <div class="field"><label>Giờ đăng ký</label><div>{{ $overtimeRequest->start_time }} – {{ $overtimeRequest->end_time }}</div></div>
        <div class="field"><label>Ghi chú nghiệp vụ</label><div class="muted">Giờ đăng ký khác giờ tính lương. HR/Kế toán đối soát theo dữ liệu chấm công (ACTUAL) khi tính lương.</div></div>
        <div class="field"><label>Lý do</label><div>{{ $overtimeRequest->reason ?? '-' }}</div></div>
        <div class="field"><label>Trạng thái</label>
            <div>
                @if($overtimeRequest->status === 'pending')
                    <span class="badge bg-warning">Chờ duyệt</span>
                @elseif($overtimeRequest->status === 'approved')
                    <span class="badge bg-success">Đã duyệt</span>
                @else
                    <span class="badge bg-danger">Từ chối</span>
                @endif
            </div>
        </div>
    </div>
@endsection
