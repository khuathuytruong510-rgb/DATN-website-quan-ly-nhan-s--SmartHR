@extends('layouts.app')

@section('title', 'Tính lương')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('accountant.dashboard') }}">Kế toán</a></li>
<li>Tính lương</li>
@endsection

@php
    $periodLocked = (bool) optional($lock ?? null)->is_locked;
    $periodLabel = sprintf('%02d/%d', $month, $year);
    $periodValue = sprintf('%04d-%02d', $year, $month);
    $periodMeta = $periodMeta ?? app(\App\Services\PayrollCalculationService::class)->periodMeta($month, $year);
@endphp

<div class="page-head">
    <div>
        <h1>Tính lương</h1>
        <p class="muted">
            Chọn kỳ → đối chiếu bảng số liệu (chấm công, nghỉ phép, tăng ca, phụ cấp, BH, thuế) → mới bấm Tính lương.
            Chỉ tính phiếu nháp / đã tính / sự cố. Không tính lại phiếu HR đã kiểm tra, Giám đốc đã duyệt, NV đã xác nhận hoặc đã thanh toán.
        </p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('accountant.payroll.index') }}">Sang bảng lương</a>
    </div>
</div>

@if(session('success'))
    <div class="alert">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert" style="background:#fee2e2;color:#dc2626;">{{ session('error') }}</div>
@endif

<div class="card">
    <h3 style="margin-top:0;">Chọn kỳ lương</h3>
    <form method="GET" action="{{ route('accountant.payroll.generate') }}" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;margin-bottom:16px;">
        <div class="field" style="margin:0;min-width:140px;">
            <label for="gen-month">Tháng</label>
            <select id="gen-month" name="month">
                @for($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}" @selected($month == $i)>Tháng {{ $i }}</option>
                @endfor
            </select>
        </div>
        <div class="field" style="margin:0;min-width:140px;">
            <label for="gen-year">Năm</label>
            <select id="gen-year" name="year">
                @for($y = 2025; $y <= 2035; $y++)
                    <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                @endfor
            </select>
        </div>
        <button class="btn" type="submit">Xem bảng kỳ</button>
    </form>

    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Kỳ</th>
                    <th>HR chốt</th>
                    <th>Phiếu</th>
                    <th>Đã tính / sự cố</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($periods as $period)
                    <tr style="{{ ($period['month'] === $month && $period['year'] === $year) ? 'background:#eff6ff;' : '' }}">
                        <td><strong>Tháng {{ $period['label'] }}</strong></td>
                        <td>
                            @if($period['locked'])
                                <span class="badge">Đã chốt</span>
                            @else
                                <span class="muted">Chưa chốt</span>
                            @endif
                        </td>
                        <td>{{ $period['total'] }}</td>
                        <td>{{ $period['calculated'] }} / {{ $period['issue'] }}</td>
                        <td style="text-align:right;">
                            <a class="btn {{ ($period['month'] === $month && $period['year'] === $year) ? 'primary' : '' }}"
                               href="{{ route('accountant.payroll.generate', ['month' => $period['month'], 'year' => $period['year']]) }}">
                                {{ ($period['month'] === $month && $period['year'] === $year) ? 'Đang xem' : 'Xem bảng' }}
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="margin-top:16px;">
    <div class="page-head" style="margin-bottom:12px;">
        <div>
            <h3 style="margin:0;">Bảng số liệu kỳ {{ $periodLabel }}</h3>
            <p class="muted" style="margin:4px 0 0;">
                Kỳ lương từ {{ $periodMeta['start_label'] }} đến {{ $periodMeta['end_label'] }}.
                {{ $periodMeta['formula_label'] }}.
            </p>
            <p class="muted" style="margin-top:6px;">
                @if($periodLocked)
                    HR đã chốt kỳ này. Dòng nền xanh sẽ được ghi khi bấm Tính lương.
                    @if($recalculableCount < $rows->count())
                        Dòng xám ({{ $rows->count() - $recalculableCount }} phiếu) đã vào vòng duyệt — không tính lại.
                    @endif
                @else
                    HR chưa chốt kỳ {{ $periodLabel }}. Có thể xem trước số liệu, nhưng chưa được tính lương.
                @endif
            </p>
        </div>
        @if($periodLocked && $recalculableCount > 0)
            <form method="POST" action="{{ route('accountant.payroll.generate_post') }}"
                  onsubmit="return confirm('Xác nhận tính lương kỳ {{ $periodLabel }} cho {{ $recalculableCount }} nhân viên?\nSố liệu đang hiển thị sẽ được ghi vào phiếu.');">
                @csrf
                <input type="hidden" name="month" value="{{ $periodValue }}">
                <button class="btn primary" type="submit">Tính lương kỳ {{ $periodLabel }}</button>
            </form>
        @elseif(! $periodLocked)
            <span class="muted">Chờ HR chốt kỳ</span>
        @else
            <span class="muted">Không còn phiếu được tính lại</span>
        @endif
    </div>

    <div class="acct-payroll-preview">
        <table>
            <thead>
                <tr>
                    <th>Nhân viên</th>
                    <th>Chức vụ</th>
                    <th class="num">Tháng</th>
                    <th class="num">Lương CB</th>
                    <th class="num">Ngày công (thực/đủ tháng)</th>
                    <th class="num">TC ngày</th>
                    <th class="num">Nghỉ phép (Có/Không)</th>
                    <th class="num">Lễ hưởng lương</th>
                    <th class="num">TC giờ</th>
                    <th class="num">Lương đi làm</th>
                    <th class="num">Lương nghỉ phép</th>
                    <th class="num">Lương lễ</th>
                    <th class="num">TC ngày (₫)</th>
                    <th class="num">TC giờ (₫)</th>
                    <th class="num">Tổng TC</th>
                    <th class="num">Phụ cấp</th>
                    <th class="num">Thưởng</th>
                    <th class="num">BH</th>
                    <th class="num">Thuế</th>
                    <th class="num">Phạt đi muộn</th>
                    <th class="num">Thực nhận</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php
                        $dailySalary = $row->daily_salary ?: ($row->base_salary / 26);
                        $actualDays = $row->working_days;
                        $calendarDays = $row->calendar_working_days ?? $row->required_working_days;
                        $actualWorkingSalary = $actualDays * $dailySalary;
                        $paidLeaveDays = $row->paid_leave_days ?? 0;
                        $paidLeaveSalary = $paidLeaveDays * $dailySalary;
                        $paidHolidayDays = $row->paid_holiday_days ?? 0;
                        $paidHolidaySalary = $row->paid_holiday_salary ?? ($paidHolidayDays * $dailySalary);
                    @endphp
                    <tr class="{{ $periodLocked && $row->can_recalculate ? 'acct-row-write' : (! $row->can_recalculate ? 'acct-row-locked' : '') }}">
                        <td>
                            <strong>{{ $row->employee->name }}</strong><br>
                            <small class="muted">{{ $row->employee->employee_code }}</small>
                        </td>
                        <td>{{ $row->employee->position ?: '—' }}</td>
                        <td class="num">{{ $periodLabel }}</td>
                        <td class="num">{{ number_format($row->base_salary) }}</td>
                        <td class="num">
                            <span style="color: {{ ($row->working_days + $paidLeaveDays) >= $calendarDays ? '#15803d' : '#dc2626' }}; font-weight:700;">
                                {{ $actualDays }}/{{ $calendarDays }}
                            </span>
                        </td>
                        <td class="num">{{ $row->overtime_days > 0 ? '+'.$row->overtime_days : '0' }}</td>
                        <td class="num">
                            {{ $paidLeaveDays }} / {{ $row->unpaid_leave_days ?? 0 }} ngày
                            @if($paidLeaveSalary > 0)
                                <br><small style="color:#15803d;font-weight:700;">+{{ number_format($paidLeaveSalary) }}</small>
                            @endif
                        </td>
                        <td class="num">
                            @if($paidHolidayDays > 0)
                                <span style="color:#15803d;font-weight:700;">{{ $paidHolidayDays }} ngày</span>
                                <br><small style="color:#15803d;font-weight:700;">+{{ number_format($paidHolidaySalary) }}</small>
                            @else
                                0
                            @endif
                        </td>
                        <td class="num">{{ number_format($row->overtime_hours, 2) }}</td>
                        <td class="num">{{ number_format($actualWorkingSalary) }}</td>
                        <td class="num">{{ number_format($paidLeaveSalary) }}</td>
                        <td class="num" style="{{ $paidHolidaySalary > 0 ? 'color:#15803d;font-weight:700;' : '' }}">{{ number_format($paidHolidaySalary) }}</td>
                        <td class="num">{{ number_format($row->overtime_day_salary ?? 0) }}</td>
                        <td class="num">{{ number_format($row->overtime_hour_salary ?? 0) }}</td>
                        <td class="num">{{ number_format($row->overtime_salary ?? 0) }}</td>
                        <td class="num">{{ number_format($row->allowance) }}</td>
                        <td class="num">{{ number_format($row->bonus ?? 0) }}</td>
                        <td class="num" style="color:#dc2626;">{{ number_format($row->insurance) }}</td>
                        <td class="num" style="{{ $row->tax > 0 ? 'color:#dc2626;' : '' }}">{{ number_format($row->tax) }}</td>
                        <td class="num" style="{{ ($row->late_penalty_fee ?? 0) > 0 ? 'color:#dc2626;font-weight:700;' : '' }}">
                            @if(($row->late_penalty_fee ?? 0) > 0)
                                − {{ number_format($row->late_penalty_fee) }}
                            @else
                                0
                            @endif
                        </td>
                        <td class="num">
                            <strong style="color: {{ $row->total_salary < 0 ? '#dc2626' : '#15803d' }};">
                                {{ number_format($row->total_salary) }}
                            </strong>
                        </td>
                        <td>
                            @if(! $row->payroll)
                                <span class="badge pending">Chưa tính — xem trước</span>
                            @else
                                <span class="badge {{ $row->can_recalculate ? '' : 'inactive' }}">
                                    {{ $workflow->statusLabel($row->status) }}
                                </span>
                                @if(! $row->can_recalculate)
                                    <br><small class="muted">Không tính lại</small>
                                @endif
                                <br>
                                <a class="btn link" href="{{ route('accountant.payroll.show', $row->payroll) }}">Chi tiết</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="22" class="empty">Không có nhân viên đang làm để tính lương kỳ này.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($periodLocked && $recalculableCount > 0 && $rows->count() > 0)
        <div style="margin-top:16px;display:flex;justify-content:flex-end;">
            <form method="POST" action="{{ route('accountant.payroll.generate_post') }}"
                  onsubmit="return confirm('Xác nhận tính lương kỳ {{ $periodLabel }} cho {{ $recalculableCount }} nhân viên?\nSố liệu đang hiển thị sẽ được ghi vào phiếu.');">
                @csrf
                <input type="hidden" name="month" value="{{ $periodValue }}">
                <button class="btn primary" type="submit">Tính lương kỳ {{ $periodLabel }}</button>
            </form>
        </div>
    @endif
</div>

<style>
.acct-payroll-preview { overflow-x: auto; margin: 0 -8px; }
.acct-payroll-preview table { min-width: 1620px; }
.acct-payroll-preview th,
.acct-payroll-preview td { white-space: nowrap; font-size: 13px; padding: 10px 8px; vertical-align: middle; }
.acct-payroll-preview th.num,
.acct-payroll-preview td.num { text-align: right; font-variant-numeric: tabular-nums; }
.acct-payroll-preview tr.acct-row-write { background: #ecfdf5; }
.acct-payroll-preview tr.acct-row-locked { background: #f1f5f9; color: #64748b; }
</style>
@endsection
