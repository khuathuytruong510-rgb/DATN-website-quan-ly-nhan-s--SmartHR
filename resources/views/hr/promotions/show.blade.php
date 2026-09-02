@extends('layouts.app')

@section('title', 'Chi tiết đề xuất thăng chức / tăng lương')

@section('content')
@php
    $user = auth()->user();
    $isManager = $user && ($user->is_hr || $user->is_admin);
    $isDirector = $user && $user->is_director;
    $employee = $promotion->employee;
    $badge = match ($promotion->status) {
        'pending' => 'background:#fef3c7;color:#92400e;',
        'approved' => 'background:#dbeafe;color:#1e40af;',
        'applied' => 'background:#dcfce7;color:#166534;',
        'rejected' => 'background:#fee2e2;color:#991b1b;',
        default => 'background:#e2e8f0;color:#475569;',
    };
@endphp
<div class="content" style="max-width:960px;">
    <div class="page-head">
        <div>
            <h1>Chi tiết đề xuất</h1>
            <p class="muted">{{ $promotion->code }} · {{ $promotion->changeTypeLabel() }} · {{ optional($employee)->name ?? '—' }}</p>
        </div>
        <div class="actions">
            <a class="btn link" href="{{ route('promotion_requests.index') }}">← Danh sách</a>
            @if($employee)
                <a class="btn" href="{{ route('employees.show', $employee) }}">Xem hồ sơ NV</a>
            @endif
        </div>
    </div>

    <div class="card" style="padding:0;overflow:hidden;margin-bottom:16px;">
        <table>
            <tbody>
                <tr>
                    <th style="width:220px;background:#f8fafc;">Mã đề xuất</th>
                    <td><code>{{ $promotion->code }}</code></td>
                    <th style="width:200px;background:#f8fafc;">Trạng thái</th>
                    <td><span class="badge" style="{{ $badge }}">{{ $promotion->statusLabel() }}</span></td>
                </tr>
                <tr>
                    <th style="background:#f8fafc;">Loại</th>
                    <td>{{ $promotion->changeTypeLabel() }}</td>
                    <th style="background:#f8fafc;">Ngày hiệu lực</th>
                    <td>{{ optional($promotion->effective_date)->format('d/m/Y') ?? '—' }}</td>
                </tr>
                <tr>
                    <th style="background:#f8fafc;">Số quyết định</th>
                    <td>{{ $promotion->document_number ?? '—' }}</td>
                    <th style="background:#f8fafc;">Người tạo</th>
                    <td>{{ optional($promotion->submittedBy)->name ?? '—' }} <span class="muted">({{ optional($promotion->created_at)->format('d/m/Y H:i') }})</span></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="grid two-cols">
        <div class="card">
            <h3 style="margin-top:0;">Thông tin nhân viên</h3>
            <div class="field"><label>Họ và tên</label><div>{{ optional($employee)->name ?? '—' }}</div></div>
            <div class="field"><label>Mã NV</label><div><code>{{ optional($employee)->employee_code ?? '—' }}</code></div></div>
            <div class="field"><label>Chức vụ hiện tại</label><div>{{ $promotion->old_position ?? optional($employee)->position ?? '—' }}</div></div>
            <div class="field"><label>Phòng ban</label><div>{{ optional(optional($employee)->department)->name ?? '—' }}</div></div>
            <div class="field"><label>Lương CB hiện tại</label><div>{{ number_format((float) $promotion->old_base_salary, 0, ',', '.') }} ₫</div></div>
            <div class="field"><label>Phụ cấp hiện tại</label><div>{{ number_format((float) $promotion->old_allowance, 0, ',', '.') }} ₫</div></div>
        </div>

        <div class="card">
            <h3 style="margin-top:0;">Thay đổi đề xuất</h3>
            <div class="field">
                <label>Chức vụ mới</label>
                <div>
                    @if($promotion->hasPositionChange())
                        <strong>{{ $promotion->new_position }}</strong>
                        <div class="muted" style="font-size:13px;">({{ optional($promotion->newPosition)->level ? 'Cấp '.$promotion->newPosition->level : '' }})</div>
                    @else
                        <span class="muted">Giữ nguyên</span>
                    @endif
                </div>
            </div>
            <div class="field">
                <label>Phòng ban mới</label>
                <div>{{ optional($promotion->department)->name ?? 'Giữ nguyên' }}</div>
            </div>
            <div class="field">
                <label>Lương CB mới</label>
                <div style="font-size:20px;font-weight:800;color:var(--primary);">
                    {{ number_format((float) $promotion->new_base_salary, 0, ',', '.') }} ₫
                    @php $diff = (float) $promotion->new_base_salary - (float) $promotion->old_base_salary; @endphp
                    <span class="muted" style="font-size:13px;">({{ $diff >= 0 ? '+' : '' }}{{ number_format($diff, 0, ',', '.') }} ₫)</span>
                </div>
            </div>
            <div class="field"><label>Phụ cấp mới</label><div>{{ number_format((float) $promotion->new_allowance, 0, ',', '.') }} ₫</div></div>
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        <h3 style="margin-top:0;">Lý do đề xuất</h3>
        <p style="white-space:pre-wrap;margin:0;">{{ $promotion->reason ?: '—' }}</p>
    </div>

    @if($promotion->isPending() || $promotion->isApproved() || $promotion->isRejected() || $promotion->isCancelled())
        <div class="card" style="margin-top:16px;">
            <h3 style="margin-top:0;">Phê duyệt</h3>
            <div class="grid two-cols" style="gap:20px;">
                <div>
                    <div class="field"><label>Người duyệt</label><div>{{ optional($promotion->reviewedBy)->name ?? '—' }}</div></div>
                    <div class="field"><label>Thời gian duyệt</label><div>{{ optional($promotion->reviewed_at)->format('d/m/Y H:i') ?? '—' }}</div></div>
                    <div class="field"><label>Nhận xét</label><div style="white-space:pre-wrap;">{{ $promotion->review_note ?: '—' }}</div></div>
                    @if($promotion->isCancelled())
                        <div class="field"><label>Lý do hủy</label><div style="white-space:pre-wrap;">{{ $promotion->cancellation_note ?: '—' }}</div></div>
                    @endif
                </div>
                <div>
                    @if($promotion->isApplied())
                        <div class="field"><label>Người áp dụng</label><div>{{ optional($promotion->appliedBy)->name ?? '—' }}</div></div>
                        <div class="field"><label>Thời gian áp dụng</label><div>{{ optional($promotion->applied_at)->format('d/m/Y H:i') ?? '—' }}</div></div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="card" style="margin-top:16px;display:flex;gap:12px;flex-wrap:wrap;">
        @if($promotion->isPending() && $isDirector)
            <form method="POST" action="{{ route('promotion_requests.approve', $promotion) }}">
                @csrf
                <div class="mb-2">
                    <label class="form-label" for="review_note">Ghi chú duyệt</label>
                    <input type="text" name="review_note" id="review_note" class="form-control" maxlength="500" value="{{ old('review_note') }}">
                </div>
                <button class="btn primary" type="submit">Duyệt đề xuất</button>
            </form>
            <form method="POST" action="{{ route('promotion_requests.reject', $promotion) }}">
                @csrf
                <div class="mb-2">
                    <label class="form-label" for="review_note_reject">Lý do từ chối <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="review_note" id="review_note_reject" class="form-control" maxlength="500" required>
                </div>
                <button class="btn danger" type="submit" data-confirm="Từ chối đề xuất này?">Từ chối</button>
            </form>
        @elseif($promotion->isApproved() && $isManager)
            <form method="POST" action="{{ route('promotion_requests.apply', $promotion) }}">
                @csrf
                <button class="btn primary" type="submit" data-confirm="Áp dụng đề xuất? Hệ thống sẽ cập nhật chức vụ, hợp đồng đang hiệu lực và ghi lịch sử lương.">Áp dụng ngay</button>
            </form>
            <form method="POST" action="{{ route('promotion_requests.cancel', $promotion) }}">
                @csrf
                <input type="hidden" name="cancellation_note" value="Hủy đề xuất đã duyệt">
                <button class="btn" type="submit" data-confirm="Hủy đề xuất đã duyệt này?">Hủy đề xuất</button>
            </form>
        @elseif($promotion->isPending() && $isManager)
            <form method="POST" action="{{ route('promotion_requests.cancel', $promotion) }}">
                @csrf
                <input type="hidden" name="cancellation_note" value="Hủy đề xuất đang chờ duyệt">
                <button class="btn" type="submit" data-confirm="Hủy đề xuất này?">Hủy đề xuất</button>
            </form>
        @endif
    </div>
</div>
@endsection