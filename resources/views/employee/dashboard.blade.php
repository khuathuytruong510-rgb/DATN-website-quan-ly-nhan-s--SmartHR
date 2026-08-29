@extends('layouts.app')

@section('title', 'Dashboard nhân viên')

@section('content')
@php
    $nameParts = preg_split('/\s+/u', trim((string) $employee->name)) ?: ['N'];
    $initials = mb_strtoupper(mb_substr($nameParts[0], 0, 1).mb_substr(end($nameParts) ?: $nameParts[0], 0, 1));
    $usedDays = $leaveLimit['used_days'] ?? 0;
    $maxDays = $leaveLimit['max_days'] ?? 2;
    $leavePct = $maxDays > 0 ? min(100, (int) round(($usedDays / $maxDays) * 100)) : 0;
    $attTone = match ($todayStatus) {
        'Đã ra' => 'ok',
        'Đã vào' => 'info',
        default => 'warn',
    };
    $contractLabel = 'Chưa được phân hợp đồng';
    $contractTone = 'muted';
    if ($currentContract) {
        $contractLabel = match ($currentContract->status) {
            'waiting_employee_signature', 'waiting_employee' => 'Chờ bạn ký',
            'waiting_director_signature', 'waiting_director' => 'Bạn đã ký — chờ Giám đốc',
            'active' => 'Có hiệu lực',
            'expired' => 'Hết hạn',
            'cancelled' => 'Đã hủy',
            default => (string) $currentContract->status,
        };
        $contractTone = match ($currentContract->status) {
            'waiting_employee_signature', 'waiting_employee' => 'warn',
            'waiting_director_signature', 'waiting_director' => 'info',
            'active' => 'ok',
            'expired', 'cancelled' => 'warn',
            default => 'muted',
        };
    }
    $payrollTone = $payrollIsIssued ? 'ok' : ($latestPayroll ? 'warn' : 'muted');
@endphp

    <div class="emp-hero">
        <div>
            <h1>Xin chào, {{ $employee->name }}</h1>
            <p>Trang tổng quan cá nhân — không phải nơi duyệt nghiệp vụ.</p>
        </div>
        <div class="emp-hero-meta">
            <span class="emp-chip"><i class="bi bi-calendar3"></i> {{ ['Chủ nhật','Thứ hai','Thứ ba','Thứ tư','Thứ năm','Thứ sáu','Thứ bảy'][now()->dayOfWeek] }}, {{ now()->format('d/m/Y') }}</span>
            @if($employee->position)
                <span class="emp-chip"><i class="bi bi-briefcase"></i> {{ $employee->position }}</span>
            @endif
            @if(optional($employee->department)->name)
                <span class="emp-chip"><i class="bi bi-building"></i> {{ $employee->department->name }}</span>
            @endif
        </div>
    </div>

    <section class="grid emp-kpis">
        <article class="emp-kpi is-{{ $attTone }}">
            <div class="emp-kpi-head">
                <h2 class="emp-kpi-label">Chấm công hôm nay</h2>
                <span class="emp-kpi-ico ico-{{ $attTone }}"><i class="bi bi-geo-alt"></i></span>
            </div>
            <div class="emp-kpi-value">{{ $todayStatus }}</div>
            <p class="emp-kpi-sub">
                {{ now()->format('d/m/Y') }}
                @if($todayAttendance?->check_in) · Vào {{ $todayAttendance->check_in->format('H:i') }}@endif
                @if($todayAttendance?->check_out) · Ra {{ $todayAttendance->check_out->format('H:i') }}@endif
            </p>
            @if($todayStatus === 'Chưa chấm')
                <a class="btn primary emp-kpi-cta" href="{{ route('me.attendance') }}">Chấm công ngay</a>
            @elseif($todayStatus === 'Đã vào')
                <a class="btn primary emp-kpi-cta" href="{{ route('me.attendance') }}">Chấm công ra</a>
            @else
                <a class="btn emp-kpi-cta" href="{{ route('me.attendance') }}">Xem chấm công</a>
            @endif
        </article>

        <article class="emp-kpi is-violet">
            <div class="emp-kpi-head">
                <h2 class="emp-kpi-label">Phép còn lại</h2>
                <span class="emp-kpi-ico ico-violet"><i class="bi bi-calendar-check"></i></span>
            </div>
            <div class="emp-kpi-value">{{ $leaveLimit['remaining_days'] ?? 0 }} ngày</div>
            <p class="emp-kpi-sub">Đã dùng {{ $usedDays }}/{{ $maxDays }} ngày trong tháng</p>
            <div class="emp-progress" aria-hidden="true"><span style="width: {{ $leavePct }}%"></span></div>
        </article>

        <article class="emp-kpi is-{{ $payrollTone }}">
            <div class="emp-kpi-head">
                <h2 class="emp-kpi-label">Lương gần nhất</h2>
                <span class="emp-kpi-ico ico-{{ $payrollTone }}"><i class="bi bi-cash-stack"></i></span>
            </div>
            @if($latestPayroll && $payrollIsIssued)
                <div class="emp-kpi-value" style="font-size:1.35rem;">{{ number_format($latestPayroll->total_salary, 0, ',', '.') }} ₫</div>
            @elseif($latestPayroll)
                <div class="emp-kpi-value" style="font-size:1.25rem;">Đang xử lý</div>
            @else
                <div class="emp-kpi-value" style="font-size:1.25rem;">Chưa có</div>
            @endif
            <p class="emp-kpi-sub">
                @if($latestPayroll)
                    Tháng {{ $latestPayroll->display_month }}
                    @if($payrollStatusLabel) · {{ $payrollStatusLabel }}@endif
                @else
                    Chưa có phiếu lương
                @endif
            </p>
            <a class="btn emp-kpi-cta" href="{{ route('me.payrolls') }}">Xem bảng lương</a>
        </article>

        <article class="emp-kpi is-{{ $contractTone }}">
            <div class="emp-kpi-head">
                <h2 class="emp-kpi-label">Hợp đồng hiện tại</h2>
                <span class="emp-kpi-ico ico-{{ $contractTone }}"><i class="bi bi-file-earmark-text"></i></span>
            </div>
            <div class="emp-kpi-value" style="font-size:1.1rem;">{{ $currentContract->title ?? 'Chưa có' }}</div>
            <p class="emp-kpi-sub"><span class="emp-badge {{ $contractTone }}">{{ $contractLabel }}</span></p>
            <a class="btn emp-kpi-cta" href="{{ route('me.contracts') }}">Xem hợp đồng</a>
        </article>

        <article class="emp-kpi {{ $unreadNotifications > 0 ? 'is-info' : 'is-muted' }}">
            <div class="emp-kpi-head">
                <h2 class="emp-kpi-label">Thông báo chưa đọc</h2>
                <span class="emp-kpi-ico {{ $unreadNotifications > 0 ? 'ico-info' : 'ico-muted' }}"><i class="bi bi-bell"></i></span>
            </div>
            <div class="emp-kpi-value">{{ $unreadNotifications }}</div>
            <p class="emp-kpi-sub">{{ $unreadNotifications > 0 ? 'Có thông báo mới cần xem' : 'Bạn đã đọc hết thông báo' }}</p>
            <a class="btn emp-kpi-cta" href="{{ route('me.notifications') }}">Xem thông báo</a>
        </article>
    </section>

    <div class="grid two-cols" style="margin-top: 22px;">
        <div class="card">
            <h2 style="margin:0 0 8px; font-size:1.05rem;">Thông tin nhân viên</h2>
            <div class="emp-dl">
                <div><label>Họ tên</label><div>{{ $employee->name }}</div></div>
                <div><label>Mã nhân viên</label><div>{{ $employee->employee_code ?? '—' }}</div></div>
                <div><label>Chức vụ</label><div>{{ $employee->position ?? 'Chưa có' }}</div></div>
                <div><label>Phòng ban</label><div>{{ optional($employee->department)->name ?? 'Chưa gán' }}</div></div>
                <div><label>Ngày vào làm</label><div>{{ optional($employee->start_date)->format('d/m/Y') ?? '—' }}</div></div>
            </div>
        </div>
        <div class="card">
            <h2 style="margin:0 0 14px; font-size:1.05rem;">Thao tác nhanh</h2>
            <div class="emp-actions">
                <a class="emp-action" href="{{ route('me.attendance') }}">
                    <i class="bi bi-geo-alt ico-ok"></i> Chấm công
                </a>
                <a class="emp-action" href="{{ route('me.leave_requests.create') }}">
                    <i class="bi bi-journal-plus ico-violet"></i> Xin nghỉ phép
                </a>
                <a class="emp-action" href="{{ route('me.payrolls') }}">
                    <i class="bi bi-cash-stack ico-info"></i> Xem bảng lương
                </a>
                <a class="emp-action" href="{{ route('me.contracts') }}">
                    <i class="bi bi-file-earmark-text ico-warn"></i> Xem hợp đồng
                </a>
            </div>
        </div>
    </div>
@endsection
