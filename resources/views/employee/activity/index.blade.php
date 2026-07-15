@extends('layouts.app')

@section('title', 'Nhật ký hoạt động')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('me.dashboard') }}">Dashboard</a></li>
<li>Nhật ký hoạt động</li>
@endsection
<div class="page-head">
    <div>
        <h1>Nhật ký hoạt động</h1>
        <p class="muted">Các hành động liên quan tới tài khoản của bạn</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('me.dashboard') }}">Quay lại</a>
    </div>
</div>

<div class="card">
    @if($logs->isEmpty())
        <div class="empty">Chưa có hoạt động.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Thời gian</th>
                    <th>Hành động</th>
                    <th>Chi tiết</th>
                </tr>
            </thead>
            <tbody>
            @foreach($logs as $l)
                <tr>
                    <td>{{ $l->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $l->action }}</td>
                    <td>{{ $l->meta }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="pagination">{{ $logs->links() }}</div>
    @endif
</div>

@endsection
