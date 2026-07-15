@extends('layouts.app')

@section('title', 'Đăng ký tăng ca')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('me.dashboard') }}">Dashboard</a></li>
<li>Đăng ký tăng ca</li>
@endsection
    <div class="page-head">
        <div>
            <h1>Đăng ký tăng ca</h1>
            <p class="muted">Danh sách yêu cầu tăng ca của bạn.</p>
        </div>
        <a class="btn primary" href="{{ route('me.overtime_requests.create') }}">Tạo yêu cầu</a>
    </div>

    <div class="card">
        @if($requests->isEmpty())
            <div class="empty">Chưa có yêu cầu tăng ca.</div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Ngày</th>
                            <th>Giờ bắt đầu</th>
                            <th>Giờ kết thúc</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requests as $r)
                            <tr>
                                <td>{{ optional($r->date)->format('d/m/Y') }}</td>
                                <td>{{ $r->start_time }}</td>
                                <td>{{ $r->end_time }}</td>
                                <td>
                                    @if($r->status === 'pending')
                                        <span class="badge bg-warning">Chờ duyệt</span>
                                    @elseif($r->status === 'approved')
                                        <span class="badge bg-success">Đã duyệt</span>
                                    @else
                                        <span class="badge bg-danger">Từ chối</span>
                                    @endif
                                </td>
                                <td><a class="btn btn-sm btn-outline-primary" href="{{ route('me.overtime_requests.show', $r) }}">Xem</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer mt-2">{{ $requests->links() }}</div>
        @endif
    </div>
@endsection
