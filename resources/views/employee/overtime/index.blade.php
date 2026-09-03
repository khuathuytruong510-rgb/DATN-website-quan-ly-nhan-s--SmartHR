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
                            <th>Nguồn</th>
                            <th>Đăng ký</th>
                            <th>Được duyệt</th>
                            <th>Thực tế</th>
                            <th>Trạng thái</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requests as $r)
                            <tr>
                                <td>{{ optional($r->date)->format('d/m/Y') }}</td>
                                <td>{{ $r->sourceLabel() }}</td>
                                <td>{{ $r->requestedWindowLabel() }}</td>
                                <td>{{ $r->approvedWindowLabel() }}</td>
                                <td>{{ $r->actualWindowLabel() }}</td>
                                <td>{{ $r->statusLabel() }}</td>
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
