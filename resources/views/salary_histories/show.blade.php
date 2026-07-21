@extends('layouts.app')

@section('title', 'Chi tiết lịch sử lương')

@section('content')
@php
    $user = auth()->user();
    $isStaff = $user && ($user->is_admin || $user->is_hr || $user->is_accountant);
    $backUrl = $isStaff ? route('salary_histories.index') : route('me.salary_histories');
@endphp

<div class="page-head">
    <div>
        <h1>Chi tiết lịch sử lương</h1>
        <p class="muted">
            @if(!empty($isPayment))
                Phiếu lương đã thanh toán kỳ {{ $salaryHistory->period }}
            @else
                Thông tin thay đổi lương của nhân viên
            @endif
        </p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ $backUrl }}">← Danh sách</a>
        @if($isStaff && $salaryHistory->payroll_id)
            <a class="btn" href="{{ route('payroll.show', $salaryHistory->payroll_id) }}">Xem phiếu lương</a>
        @endif
    </div>
</div>

<div class="card">
    <div class="grid two-cols">
        <div>
            <h3>Thông tin nhân viên</h3>
            <div class="field"><label>Họ và tên</label><div>{{ optional($salaryHistory->employee)->name ?? '—' }}</div></div>
            <div class="field"><label>Email</label><div>{{ optional($salaryHistory->employee)->email ?? '—' }}</div></div>
            <div class="field"><label>Số điện thoại</label><div>{{ optional($salaryHistory->employee)->phone ?? '—' }}</div></div>
            <div class="field"><label>Phòng ban</label><div>{{ optional(optional($salaryHistory->employee)->department)->name ?? '—' }}</div></div>
            <div class="field"><label>Chức vụ</label><div>{{ $salaryHistory->position ?? optional($salaryHistory->employee)->position ?? '—' }}</div></div>
        </div>

        <div>
            <h3>{{ !empty($isPayment) ? 'Thông tin thanh toán' : 'Thông tin lịch sử lương' }}</h3>
            <div class="field"><label>Mã</label><div>{{ $salaryHistory->code ?? ('SH' . $salaryHistory->id) }}</div></div>
            <div class="field"><label>Kỳ lương</label><div>{{ $salaryHistory->period ?? '—' }}</div></div>
            <div class="field"><label>{{ !empty($isPayment) ? 'Ngày thanh toán' : 'Ngày áp dụng' }}</label><div>{{ $salaryHistory->effective_date?->format('d/m/Y') ?? '—' }}</div></div>
            <div class="field"><label>Loại</label><div>{{ $salaryHistory->change_type ?? '—' }}</div></div>

            <div class="row">
                <div class="col-md-6">
                    <div class="field"><label>{{ !empty($isPayment) ? 'Lương cơ bản' : 'Mức lương cũ' }}</label><div>{{ number_format($old, 0, ',', '.') }} ₫</div></div>
                </div>
                <div class="col-md-6">
                    <div class="field"><label>{{ !empty($isPayment) ? 'Thực nhận' : 'Mức lương mới' }}</label><div><strong>{{ number_format($new, 0, ',', '.') }} ₫</strong></div></div>
                </div>
            </div>

            @if(empty($isPayment))
                <div class="field"><label>Chênh lệch</label><div>{{ number_format($difference, 0, ',', '.') }} ₫ ({{ $percent !== null ? $percent . '%' : 'n/a' }})</div></div>
            @endif

            <div class="field"><label>Trạng thái</label>
                <div>
                    @php
                        $status = $salaryHistory->status ?? 'pending';
                        $badgeStyle = 'background:#e2e8f0;color:#475569;';
                        if (in_array($status, ['applied', 'paid'], true)) $badgeStyle = 'background:#dcfce7;color:#166534;';
                        if ($status === 'pending') $badgeStyle = 'background:#fef3c7;color:#92400e;';
                    @endphp
                    <span class="badge" style="{{ $badgeStyle }}">{{ ucfirst($status) }}</span>
                </div>
            </div>

            <div class="field"><label>Người cập nhật</label><div>{{ optional($salaryHistory->updatedBy)->name ?? '—' }}</div></div>
            <div class="field"><label>Mã chứng từ</label><div>{{ $salaryHistory->document_number ?? '—' }}</div></div>

            <h4 style="margin-top:18px;">Chi tiết khoản</h4>
            <table>
                <tr><th>Phụ cấp</th><td>{{ number_format($allowances['other'] ?? $allowanceTotal, 0, ',', '.') }} ₫</td></tr>
                @if(!empty($allowances['overtime']))
                    <tr><th>Tăng ca</th><td>{{ number_format($allowances['overtime'], 0, ',', '.') }} ₫</td></tr>
                @endif
                <tr><th>Thưởng</th><td>{{ number_format($rewards, 0, ',', '.') }} ₫</td></tr>
                <tr><th>Khấu trừ</th><td>{{ number_format($deductions, 0, ',', '.') }} ₫</td></tr>
                <tr><th>Thuế</th><td>{{ number_format($tax, 0, ',', '.') }} ₫</td></tr>
                <tr><th>Bảo hiểm</th><td>{{ number_format($insurance, 0, ',', '.') }} ₫</td></tr>
                <tr><th><strong>Thực nhận</strong></th><td><strong>{{ number_format($net, 0, ',', '.') }} ₫</strong></td></tr>
            </table>

            <h4 style="margin-top:18px;">Ghi chú</h4>
            <div class="field"><div style="white-space:pre-wrap;">{{ $salaryHistory->notes ?? '—' }}</div></div>
        </div>
    </div>
</div>
@endsection
