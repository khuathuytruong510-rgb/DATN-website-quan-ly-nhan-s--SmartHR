@extends('layouts.app')

@section('title', 'Chi tiết lịch sử lương')

@section('content')
@php
    $user = auth()->user();
    $isStaff = $user && $user->isStaffUser();
    $backUrl = $isStaff ? route('salary_histories.index') : route('me.salary_histories');
    $employee = $salaryHistory->employee;
    $status = $salaryHistory->status ?? 'pending';
    $badgeStyle = match (true) {
        in_array($status, ['applied', 'paid'], true) => 'background:#dcfce7;color:#166534;',
        $status === 'pending' => 'background:#fef3c7;color:#92400e;',
        default => 'background:#e2e8f0;color:#475569;',
    };
@endphp

<div class="content" style="max-width:980px;">
    <div class="page-head">
        <div>
            <h1>Chi tiết lịch sử lương</h1>
            <p class="muted">
                {{ optional($employee)->name ?? '—' }}
                · Kỳ {{ $salaryHistory->period ?? '—' }}
                @if(!empty($isPayment))
                    · Đã thanh toán
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

    {{-- Hero số tiền --}}
    <div class="card" style="margin-bottom:16px;display:flex;flex-wrap:wrap;gap:24px;align-items:center;justify-content:space-between;">
        <div>
            <div style="color:#64748b;font-size:13px;margin-bottom:4px;">
                {{ !empty($isPayment) ? 'Thực nhận' : 'Mức lương mới' }}
            </div>
            <div style="font-size:32px;font-weight:800;color:var(--primary);line-height:1.1;">
                {{ number_format($net, 0, ',', '.') }} ₫
            </div>
            <div style="margin-top:10px;display:flex;flex-wrap:gap:8px;align-items:center;">
                <span class="badge" style="{{ $badgeStyle }}">{{ ucfirst($status) }}</span>
                <span class="muted" style="font-size:13px;">{{ $salaryHistory->change_type ?? '—' }}</span>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(2,minmax(120px,1fr));gap:16px 28px;min-width:260px;">
            <div>
                <div style="color:#64748b;font-size:12px;">{{ !empty($isPayment) ? 'Lương CB' : 'Mức cũ' }}</div>
                <div style="font-weight:700;margin-top:2px;">{{ number_format($old, 0, ',', '.') }} ₫</div>
            </div>
            <div>
                <div style="color:#64748b;font-size:12px;">{{ !empty($isPayment) ? 'Ngày TT' : 'Ngày áp dụng' }}</div>
                <div style="font-weight:700;margin-top:2px;">{{ $salaryHistory->effective_date?->format('d/m/Y') ?? '—' }}</div>
            </div>
            <div>
                <div style="color:#64748b;font-size:12px;">Mã</div>
                <div style="font-weight:700;margin-top:2px;">{{ $salaryHistory->code ?? ('SH'.$salaryHistory->id) }}</div>
            </div>
            <div>
                <div style="color:#64748b;font-size:12px;">Chứng từ</div>
                <div style="font-weight:700;margin-top:2px;">{{ $salaryHistory->document_number ?? '—' }}</div>
            </div>
        </div>
    </div>

    <div class="grid two-cols">
        <div class="card">
            <h3 style="margin-top:0;">Thông tin nhân viên</h3>
            <div style="margin-bottom:14px;">
                <span style="color:#64748b;font-size:13px;">Họ và tên</span>
                <p style="margin:4px 0 0;font-weight:600;">{{ optional($employee)->name ?? '—' }}</p>
            </div>
            <div style="margin-bottom:14px;">
                <span style="color:#64748b;font-size:13px;">Email</span>
                <p style="margin:4px 0 0;font-weight:600;">{{ optional($employee)->email ?? '—' }}</p>
            </div>
            <div style="margin-bottom:14px;">
                <span style="color:#64748b;font-size:13px;">Số điện thoại</span>
                <p style="margin:4px 0 0;font-weight:600;">{{ optional($employee)->phone ?? '—' }}</p>
            </div>
            <div style="margin-bottom:14px;">
                <span style="color:#64748b;font-size:13px;">Phòng ban</span>
                <p style="margin:4px 0 0;font-weight:600;">{{ optional(optional($employee)->department)->name ?? '—' }}</p>
            </div>
            <div>
                <span style="color:#64748b;font-size:13px;">Chức vụ</span>
                <p style="margin:4px 0 0;font-weight:600;">{{ $salaryHistory->position ?? optional($employee)->position ?? '—' }}</p>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-top:0;">Chi tiết khoản</h3>

            <div style="margin-bottom:12px;display:flex;justify-content:space-between;gap:12px;">
                <span style="color:#64748b;">Phụ cấp</span>
                <strong style="color:#166534;">+ {{ number_format($allowances['other'] ?? 0, 0, ',', '.') }} ₫</strong>
            </div>
            @if(!empty($allowances['overtime']))
                <div style="margin-bottom:12px;display:flex;justify-content:space-between;gap:12px;">
                    <span style="color:#64748b;">Tăng ca</span>
                    <strong style="color:#166534;">+ {{ number_format($allowances['overtime'], 0, ',', '.') }} ₫</strong>
                </div>
            @endif
            <div style="margin-bottom:12px;display:flex;justify-content:space-between;gap:12px;">
                <span style="color:#64748b;">Thưởng</span>
                <strong style="color:#166534;">+ {{ number_format($rewards, 0, ',', '.') }} ₫</strong>
            </div>
            <div style="margin-bottom:12px;display:flex;justify-content:space-between;gap:12px;">
                <span style="color:#64748b;">Khấu trừ</span>
                <strong style="color:#dc2626;">− {{ number_format($deductions, 0, ',', '.') }} ₫</strong>
            </div>
            @if(($latePenaltyFee ?? 0) > 0)
                <div style="margin-bottom:12px;display:flex;justify-content:space-between;gap:12px;">
                    <span style="color:#64748b;">Phạt đi muộn</span>
                    <strong style="color:#dc2626;">− {{ number_format($latePenaltyFee, 0, ',', '.') }} ₫</strong>
                </div>
            @endif
            <div style="margin-bottom:12px;display:flex;justify-content:space-between;gap:12px;">
                <span style="color:#64748b;">Thuế</span>
                <strong style="color:#dc2626;">− {{ number_format($tax, 0, ',', '.') }} ₫</strong>
            </div>
            <div style="margin-bottom:12px;display:flex;justify-content:space-between;gap:12px;">
                <span style="color:#64748b;">Bảo hiểm</span>
                <strong style="color:#dc2626;">− {{ number_format($insurance, 0, ',', '.') }} ₫</strong>
            </div>

            @if(empty($isPayment))
                <div style="margin-bottom:12px;display:flex;justify-content:space-between;gap:12px;">
                    <span style="color:#64748b;">Chênh lệch</span>
                    <strong>{{ number_format($difference, 0, ',', '.') }} ₫ @if($percent !== null)( {{ $percent }}% )@endif</strong>
                </div>
            @endif

            <div style="border-top:1px solid var(--line);padding-top:14px;display:flex;justify-content:space-between;align-items:center;gap:12px;">
                <span style="color:#64748b;">Thực nhận</span>
                <strong style="font-size:22px;color:var(--primary);">{{ number_format($net, 0, ',', '.') }} ₫</strong>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        <h3 style="margin-top:0;">Ghi chú & cập nhật</h3>
        <div class="grid two-cols" style="gap:20px;">
            <div>
                <span style="color:#64748b;font-size:13px;">Người cập nhật</span>
                <p style="margin:4px 0 0;font-weight:600;">{{ optional($salaryHistory->updatedBy)->name ?? '—' }}</p>
                <span style="color:#64748b;font-size:13px;display:block;margin-top:12px;">Thời gian</span>
                <p style="margin:4px 0 0;font-weight:600;">{{ optional($salaryHistory->updated_at)?->format('d/m/Y H:i') ?? '—' }}</p>
            </div>
            <div>
                <span style="color:#64748b;font-size:13px;">Nội dung</span>
                <p style="margin:6px 0 0;white-space:pre-wrap;line-height:1.5;">{{ $salaryHistory->notes ?: '—' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
