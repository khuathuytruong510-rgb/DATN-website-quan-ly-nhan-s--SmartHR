@extends('layouts.app')

@section('title', 'Chi tiết phúc lợi')

@section('content')
<div class="content">
    <div class="page-head">
        <div>
            <h1>Chi tiết phúc lợi</h1>
            <p class="muted">Thông tin chi tiết gói phúc lợi cho nhân viên.</p>
        </div>
        <div class="actions">
            <a class="btn" href="{{ route('benefits.index') }}">Quay lại</a>
            <a class="btn primary" href="{{ route('benefits.edit', $benefit) }}">Sửa</a>
        </div>
    </div>

    <div class="card">
        <div class="field"><label>Mã phúc lợi</label><div>{{ $benefit->code ?? '---' }}</div></div>
        <div class="field"><label>Nhân viên</label><div>{{ optional($benefit->employee)->name ?? '---' }}</div></div>
        <div class="field"><label>Tiêu đề</label><div>{{ $benefit->title }}</div></div>
        <div class="field"><label>Loại</label><div>{{ ucfirst($benefit->type) }}</div></div>
        <div class="field"><label>Áp dụng cho</label><div>{{ $benefit->applies_to ?? '---' }}</div></div>
        <div class="field"><label>Điều kiện</label><div>{{ $benefit->condition ?? '---' }}</div></div>
        <div class="field"><label>Đơn vị</label><div>{{ $benefit->unit ?? '---' }}</div></div>
        <div class="field"><label>Số tiền</label><div>{{ $benefit->amount ? number_format($benefit->amount, 2) : '---' }}</div></div>
        <div class="field"><label>Ngày hiệu lực</label><div>{{ optional($benefit->effective_date)->format('d/m/Y') ?? '---' }}</div></div>
        <div class="field"><label>Ngày hết hạn</label><div>{{ optional($benefit->expiry_date)->format('d/m/Y') ?? '---' }}</div></div>
        <div class="field"><label>Trạng thái ứng dụng</label><div>{{ ucfirst($benefit->application_status) }}</div></div>
        <div class="field"><label>Trạng thái phê duyệt</label><div>{{ ucfirst($benefit->approval_status) }}</div></div>
        <div class="field"><label>Trạng thái</label><div>{{ ucfirst($benefit->status) }}</div></div>
        <div class="field"><label>Người tạo</label><div>{{ optional($benefit->creator)->name ?? '---' }}</div></div>
        <div class="field"><label>Người duyệt</label><div>{{ optional($benefit->approvedBy)->name ?? '---' }}</div></div>
        <div class="field"><label>Ngày duyệt</label><div>{{ optional($benefit->approved_at)->format('d/m/Y H:i') ?? '---' }}</div></div>
        <div class="field"><label>Ghi chú</label><div>{{ $benefit->notes ?? '---' }}</div></div>
    </div>
</div>

<style>
    .content { max-width: 760px; }
    .page-head { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 22px; }
    .muted { color: #64748b; margin: 0; }
    .card { background: var(--panel); border: 1px solid var(--line); border-radius: 12px; padding: 24px; }
    .field { margin-bottom: 18px; }
    label { display: block; font-weight: 700; margin-bottom: 8px; }
    .field > div { padding: 12px 14px; border-radius: 8px; background: #f8fafc; border: 1px solid #cbd5e1; }
    .actions { display: flex; gap: 12px; }
    .btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 14px; border-radius: 8px; border: none; text-decoration: none; font-weight: 700; background: #f8fafc; color: inherit; }
    .btn.primary { background: #2563eb; color: #fff; }
</style>
@endsection
