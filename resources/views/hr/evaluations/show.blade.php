@extends('layouts.app')

@section('title', 'Chi tiết đánh giá')

@section('content')
<div class="content">
    <div class="page-head">
        <div>
            <h1>Chi tiết đánh giá</h1>
            <p class="muted">Thông tin đánh giá nhân viên theo tháng</p>
        </div>
        <div class="actions">
            <a class="btn" href="{{ route('evaluations.index') }}">Quay lại</a>
            <a class="btn primary" href="{{ route('evaluations.edit', $evaluation) }}">Sửa</a>
        </div>
    </div>

    <div class="card">
        <div class="field"><label>Nhân viên</label><div>{{ optional($evaluation->employee)->name ?? '---' }}</div></div>
        <div class="field"><label>Phòng ban</label><div>{{ optional($evaluation->employee->department)->name ?? '---' }}</div></div>
        <div class="field"><label>Tháng đánh giá</label><div>{{ $evaluation->month }}</div></div>
        <div class="field"><label>Người đánh giá</label><div>{{ optional($evaluation->evaluator)->name ?? 'Hệ thống' }}</div></div>
        <div class="field"><label>Trạng thái</label><div>{{ ucfirst($evaluation->status) }}</div></div>
        <div class="field"><label>Đã duyệt bởi</label><div>{{ optional($evaluation->approvedBy)->name ?? '---' }}</div></div>
        <div class="field"><label>Ngày duyệt</label><div>{{ optional($evaluation->approved_at)->format('d/m/Y H:i') ?? '---' }}</div></div>

        <div class="divider"></div>

        <h2>Tiêu chí chi tiết</h2>
        <div class="field"><label>Đi đúng giờ</label><div>{{ $evaluation->punctuality }} / 10</div></div>
        <div class="field"><label>Hoàn thành công việc</label><div>{{ $evaluation->task_completion }} / 30</div></div>
        <div class="field"><label>Chất lượng công việc</label><div>{{ $evaluation->quality }} / 20</div></div>
        <div class="field"><label>Kỹ năng chuyên môn</label><div>{{ $evaluation->technical_skill }} / 10</div></div>
        <div class="field"><label>Trách nhiệm</label><div>{{ $evaluation->responsibility }} / 10</div></div>
        <div class="field"><label>Làm việc nhóm</label><div>{{ $evaluation->teamwork }} / 10</div></div>
        <div class="field"><label>Thái độ</label><div>{{ $evaluation->attitude }} / 10</div></div>
        <div class="field"><label>Tổng điểm</label><div>{{ $evaluation->score_total }} / 100</div></div>
        <div class="field"><label>Phân loại</label><div>{{ $evaluation->classification }}</div></div>

        <div class="divider"></div>

        <div class="field"><label>Tóm tắt</label><div>{{ $evaluation->summary ?? '---' }}</div></div>
        <div class="field"><label>Nhận xét</label><div>{{ $evaluation->comments ?? '---' }}</div></div>
    </div>
</div>

<style>
    .content { max-width: 760px; }
    .page-head { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 22px; }
    .muted { color: #64748b; margin: 0; }
    .card { background: var(--panel); border: 1px solid var(--line); border-radius: 12px; padding: 24px; }
    .field { margin-bottom: 18px; }
    label { display: block; font-weight: 700; margin-bottom: 6px; }
    .field > div { padding: 12px 14px; border-radius: 8px; background: #f8fafc; border: 1px solid #cbd5e1; }
    .divider { height: 1px; background: var(--line); margin: 24px 0; }
    .actions { display: flex; gap: 12px; align-items: center; }
    .btn { display: inline-flex; align-items: center; padding: 10px 14px; border-radius: 8px; border: none; cursor: pointer; font-weight: 700; text-decoration: none; background: #f8fafc; color: inherit; }
    .btn.primary { background: #2563eb; color: white; }
</style>
@endsection
