@extends('layouts.app')

@section('title', 'Chi tiết phúc lợi')

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
<div class="max-w-4xl">
    <div class="page-head">
        <div>
            <h1>Chi tiết phúc lợi</h1>
        </div>
        <div class="page-actions">
            <a class="btn" href="{{ route('benefits.index') }}">Quay lại</a>
            <a class="btn primary" href="{{ route('benefits.edit', $benefit) }}">Sửa</a>
        </div>
    </div>

    <div class="card">
        <div class="emp-dl">
            <div><label>Mã phúc lợi</label><div>{{ $benefit->code ?? '—' }}</div></div>
            <div><label>Nhân viên</label><div>{{ optional($benefit->employee)->name ?? '—' }}</div></div>
            <div><label>Tiêu đề</label><div>{{ $benefit->title }}</div></div>
            <div><label>Loại</label><div>{{ ucfirst($benefit->type) }}</div></div>
            <div><label>Áp dụng cho</label><div>{{ $benefit->applies_to ?? '—' }}</div></div>
            <div><label>Điều kiện</label><div>{{ $benefit->condition ?? '—' }}</div></div>
            <div><label>Đơn vị</label><div>{{ $benefit->unit ?? '—' }}</div></div>
            <div><label>Số tiền</label><div>{{ $benefit->amount ? number_format($benefit->amount, 0, ',', '.') : '—' }}</div></div>
            <div><label>Ngày hiệu lực</label><div>{{ optional($benefit->effective_date)->format('d/m/Y') ?? '—' }}</div></div>
            <div><label>Ngày hết hạn</label><div>{{ optional($benefit->expiry_date)->format('d/m/Y') ?? '—' }}</div></div>
            <div><label>Trạng thái ứng dụng</label><div><span class="badge {{ $statusClass($benefit->application_status) }}">{{ $statusLabel($benefit->application_status) }}</span></div></div>
            <div><label>Trạng thái phê duyệt</label><div><span class="badge {{ $statusClass($benefit->approval_status) }}">{{ $statusLabel($benefit->approval_status) }}</span></div></div>
            <div><label>Trạng thái</label><div><span class="badge {{ $statusClass($benefit->status) }}">{{ $statusLabel($benefit->status) }}</span></div></div>
            <div><label>Người tạo</label><div>{{ optional($benefit->creator)->name ?? '—' }}</div></div>
            <div><label>Người duyệt</label><div>{{ optional($benefit->approvedBy)->name ?? '—' }}</div></div>
            <div><label>Ngày duyệt</label><div>{{ optional($benefit->approved_at)->format('d/m/Y H:i') ?? '—' }}</div></div>
            <div><label>Ghi chú</label><div>{{ $benefit->notes ?? '—' }}</div></div>
        </div>
    </div>
</div>
@endsection
