@extends('layouts.app')

@section('title', 'Nhật ký hoạt động')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('accountant.dashboard') }}">Kế toán</a></li>
<li>Nhật ký</li>
@endsection

<div class="page-head">
    <div>
        <h1>Nhật ký hoạt động</h1>
        <p class="muted">Lịch sử tính lương, chốt kỳ và thanh toán — hiển thị bằng ngôn ngữ dễ đọc.</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('accountant.dashboard') }}">Quay lại</a>
    </div>
</div>

<div class="card">
    @if($logs->isEmpty())
        <div class="empty">Chưa có nhật ký.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Thời gian</th>
                    <th>Người thực hiện</th>
                    <th>Hành động</th>
                    <th>Chi tiết</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr>
                        <td>{{ optional($log->created_at)->format('d/m/Y H:i') }}</td>
                        <td>{{ optional($log->user)->name ?: '—' }}</td>
                        <td style="font-weight:600;">{{ $log->label() }}</td>
                        <td>{{ $log->detail() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="pagination">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
