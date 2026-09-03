@extends('layouts.app')

@section('title', 'Phúc lợi của tôi')

@section('content')
@php
    $statusLabel = function (?string $status): string {
        return match (strtolower((string) $status)) {
            'active' => 'Đang áp dụng',
            'inactive' => 'Ngưng áp dụng',
            'pending' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            default => $status ? ucfirst($status) : '—',
        };
    };
    $statusClass = function (?string $status): string {
        return match (strtolower((string) $status)) {
            'active', 'approved' => 'ok',
            'pending' => 'pending',
            'inactive', 'rejected' => 'danger',
            default => 'muted',
        };
    };
@endphp
<div class="page-head">
    <div>
        <h1>Phúc lợi của tôi</h1>
    </div>
</div>

@if($benefits->count())
    <div class="card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã</th>
                        <th>Tiêu đề</th>
                        <th>Loại</th>
                        <th>Áp dụng cho</th>
                        <th>Đơn vị</th>
                        <th>Số tiền</th>
                        <th>Trạng thái ứng dụng</th>
                        <th>Trạng thái phê duyệt</th>
                        <th>Hiệu lực</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($benefits as $index => $benefit)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><code>{{ $benefit->code ?? '—' }}</code></td>
                            <td>{{ $benefit->title }}</td>
                            <td>{{ ucfirst($benefit->type) }}</td>
                            <td>{{ $benefit->applies_to ?? '—' }}</td>
                            <td>{{ $benefit->unit ?? '—' }}</td>
                            <td>{{ $benefit->amount ? number_format($benefit->amount, 0, ',', '.') : '—' }}</td>
                            <td><span class="badge {{ $statusClass($benefit->application_status) }}">{{ $statusLabel($benefit->application_status) }}</span></td>
                            <td><span class="badge {{ $statusClass($benefit->approval_status) }}">{{ $statusLabel($benefit->approval_status) }}</span></td>
                            <td>{{ optional($benefit->effective_date)->format('d/m/Y') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="card">
        <div class="empty">Hiện chưa có phúc lợi nào dành cho bạn.</div>
    </div>
@endif
@endsection
