@extends('layouts.app')

@section('title', 'Chi tiết bảng lương')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('accountant.dashboard') }}">Kế toán</a></li>
<li><a href="{{ route('accountant.payroll.index') }}">Quản lý bảng lương</a></li>
<li>Chi tiết</li>
@endsection

@php
    $employee = $payroll->employee;
    $f = $formula;
    $money = fn ($value) => number_format((float) $value, 0, '.', ',') . ' ₫';
    $pct = number_format(((float) $f['insurance_rate']) * 100, 1, ',', '.') . '%';
@endphp

<div class="page-head">
    <div>
        <h1>Chi tiết bảng lương</h1>
        <p class="muted">Phiếu lương {{ optional($employee)->name }} · Tháng {{ $payroll->display_month }}</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('accountant.payroll.index') }}">Quay lại</a>
        @if($workflow->isDirectorApproved($payroll->status) || $workflow->canPay($payroll))
        <form method="POST" action="{{ route('accountant.payroll.send_email', $payroll) }}" style="display:inline;">
            @csrf
            <button class="btn" type="submit">Gửi email xác nhận</button>
        </form>
        @endif
        @if(in_array($payroll->status, \App\Services\PayrollPaymentWorkflowService::recalculableStatuses(), true))
        <form method="POST" action="{{ route('accountant.payroll.recalculate', $payroll) }}" style="display:inline;">
            @csrf
            <button class="btn" type="submit">Tính lại</button>
        </form>
        @endif
        @if($workflow->canPay($payroll))
        <a class="btn primary" href="{{ route('payroll.payment.show', $payroll) }}">Thanh toán</a>
        @endif
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:16px;">
    <div class="card" style="margin:0;">
        <p class="muted" style="margin:0 0 6px;font-size:13px;">① Lương cơ bản (hợp đồng)</p>
        <p style="margin:0;font-size:22px;font-weight:800;">{{ $money($f['base_salary']) }}</p>
        <p class="muted" style="margin:8px 0 0;font-size:12px;">Cơ sở tính lương ngày và bảo hiểm. Không cộng vào thực nhận.</p>
    </div>
    <div class="card" style="margin:0;">
        <p class="muted" style="margin:0 0 6px;font-size:13px;">② Lương theo ngày công</p>
        <p style="margin:0;font-size:22px;font-weight:800;">{{ $money($f['work_pay']) }}</p>
        <p class="muted" style="margin:8px 0 0;font-size:12px;">
            {{ $f['work_days'] }}/{{ $f['calendar_days'] }} ngày công nếu đi làm đủ × lương ngày (CB ÷ {{ $f['standard_days'] }}).
        </p>
    </div>
    <div class="card" style="margin:0;">
        <p class="muted" style="margin:0 0 6px;font-size:13px;">③ Lương thực nhận</p>
        <p style="margin:0;font-size:22px;font-weight:800;color:var(--primary);">{{ $money($f['net']) }}</p>
        <p class="muted" style="margin:8px 0 0;font-size:12px;">Tổng thu nhập − bảo hiểm − thuế − khấu trừ.</p>
    </div>
</div>

<div class="grid two-cols">
    <div class="card">
        <h3 style="margin-top:0;">Thông tin phiếu</h3>
        <div style="margin-bottom:14px;">
            <span class="muted" style="font-size:13px;">Nhân viên</span>
            <p style="margin:4px 0 0;font-weight:700;">{{ optional($employee)->name }}</p>
            <p class="muted" style="margin:4px 0 0;font-size:13px;">
                {{ optional($employee)->employee_code }}
                @if(optional($employee)->email)
                    · {{ $employee->email }}
                @endif
            </p>
        </div>
        <div style="margin-bottom:14px;">
            <span class="muted" style="font-size:13px;">Chức vụ</span>
            <p style="margin:4px 0 0;font-weight:600;">{{ optional($employee)->position ?: '—' }}</p>
        </div>
        <div style="margin-bottom:14px;">
            <span class="muted" style="font-size:13px;">Kỳ lương</span>
            <p style="margin:4px 0 0;font-weight:600;">Tháng {{ $payroll->display_month }}</p>
            <p class="muted" style="margin:4px 0 0;font-size:13px;">Từ {{ $f['period_start'] }} đến {{ $f['period_end'] }}</p>
        </div>
        <div>
            <span class="muted" style="font-size:13px;">Trạng thái</span>
            <p style="margin:4px 0 0;">
                <span class="badge">{{ $workflow->statusLabel($payroll->status) }}</span>
            </p>
        </div>
        @if($payroll->director_approved_at || $payroll->director_approved_name)
        <div style="margin-top:14px;">
            <span class="muted" style="font-size:13px;">Giám đốc phê duyệt</span>
            <p style="margin:4px 0 0;font-weight:600;">{{ $payroll->directorApproverLabel() }}</p>
            <p class="muted" style="margin:4px 0 0;font-size:12px;">
                Tại thời điểm duyệt
                @if($payroll->director_approved_at)
                    · {{ $payroll->director_approved_at->format('d/m/Y H:i') }}
                @endif
                — không đổi khi thay người giữ chức
            </p>
        </div>
        @endif
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Dữ liệu công trong tháng</h3>
        <table>
            <tbody>
                <tr>
                    <td>Số ngày trong kỳ lương</td>
                    <td style="text-align:right;font-weight:700;">{{ $f['days_in_period'] }} ngày</td>
                </tr>
                <tr>
                    <td>Chủ nhật (được nghỉ)</td>
                    <td style="text-align:right;font-weight:700;">{{ $f['weekend_days'] }} ngày</td>
                </tr>
                <tr>
                    <td>Ngày lễ hưởng lương (Điều 112 BLLĐ)</td>
                    <td style="text-align:right;font-weight:700;">{{ $f['holiday_days'] }} ngày</td>
                </tr>
                <tr>
                    <td>Ngày công nếu đi làm đủ</td>
                    <td style="text-align:right;font-weight:700;">{{ $f['calendar_days'] }} ngày</td>
                </tr>
                <tr>
                    <td>Ngày công chuẩn (tính lương ngày)</td>
                    <td style="text-align:right;font-weight:700;">{{ $f['standard_days'] }} ngày</td>
                </tr>
                <tr>
                    <td>Ngày công thực tế</td>
                    <td style="text-align:right;font-weight:700;">{{ $f['work_days'] }} ngày</td>
                </tr>
                <tr>
                    <td>Nghỉ phép có lương / không lương</td>
                    <td style="text-align:right;font-weight:700;">{{ $f['paid_leave_days'] }} / {{ $f['unpaid_leave_days'] }} ngày</td>
                </tr>
                <tr>
                    <td>Nghỉ lễ hưởng lương (đã trả 100%)</td>
                    <td style="text-align:right;font-weight:700;">{{ $f['paid_holiday_days'] }} ngày</td>
                </tr>
                <tr>
                    <td>Ngày hưởng lương (đi làm + phép + lễ)</td>
                    <td style="text-align:right;font-weight:700;">{{ $f['payable_days'] }} ngày</td>
                </tr>
                <tr>
                    <td>Tăng ca (ngày / giờ)</td>
                    <td style="text-align:right;font-weight:700;">
                        {{ $payroll->overtime_days ?? 0 }}
                        /
                        {{ number_format((float) ($payroll->overtime_hours ?? 0), 2) }}
                    </td>
                </tr>
                <tr>
                    <td>Lương ngày (Lương CB ÷ {{ $f['standard_days'] }})</td>
                    <td style="text-align:right;font-weight:700;">{{ $money($f['daily_salary']) }}</td>
                </tr>
            </tbody>
        </table>
        <p class="muted" style="margin:12px 0 0;font-size:12px;">
            {{ $f['full_attendance_formula'] }}.
            Lương ngày vẫn lấy lương CB ÷ {{ $f['standard_days'] }}.
        </p>
        @if(!empty($f['holidays']))
            <p class="muted" style="margin:8px 0 0;font-size:12px;">
                Lễ trong kỳ:
                {{ collect($f['holidays'])->map(fn ($h) => \Carbon\Carbon::parse($h['date'])->format('d/m').' '.$h['name'])->implode('; ') }}.
            </p>
        @endif
    </div>
</div>

<div class="card" style="margin-top:16px;">
    <h3 style="margin-top:0;">Chi tiết tính lương</h3>
    <table>
        <thead>
            <tr>
                <th>Khoản mục</th>
                <th style="text-align:right;">Số tiền</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="2" style="background:#f8fafc;font-weight:700;">Thu nhập</td>
            </tr>
            <tr>
                <td>Lương cơ bản (hợp đồng) — chỉ là cơ sở tính</td>
                <td style="text-align:right;color:#64748b;">{{ $money($f['base_salary']) }}</td>
            </tr>
            <tr>
                <td>Lương đi làm ({{ $f['work_days'] }} ngày)</td>
                <td style="text-align:right;color:#166534;font-weight:700;">+ {{ $money($f['work_pay']) }}</td>
            </tr>
            <tr>
                <td>Lương nghỉ phép có lương ({{ $f['paid_leave_days'] }} ngày)</td>
                <td style="text-align:right;color:#166534;font-weight:700;">+ {{ $money($f['leave_pay']) }}</td>
            </tr>
            <tr>
                <td>Lương ngày lễ hưởng lương ({{ $f['paid_holiday_days'] }} ngày × 100%)</td>
                <td style="text-align:right;color:#166534;font-weight:700;">+ {{ $money($f['holiday_pay']) }}</td>
            </tr>
            <tr>
                <td>Đi làm ngày lễ (Điều 98: × {{ rtrim(rtrim(number_format((float) $f['holiday_work_rate'], 2, ',', '.'), '0'), ',') }}, chưa kể lương ngày lễ)</td>
                <td style="text-align:right;color:#166534;font-weight:700;">+ {{ $money($f['holiday_work_pay']) }}</td>
            </tr>
            <tr>
                <td>Đi làm Chủ nhật (Điều 98: × {{ rtrim(rtrim(number_format((float) $f['weekly_rest_rate'], 2, ',', '.'), '0'), ',') }})</td>
                <td style="text-align:right;color:#166534;font-weight:700;">+ {{ $money($f['weekly_rest_pay']) }}</td>
            </tr>
            <tr>
                <td>Tăng ca giờ (đơn giá × {{ rtrim(rtrim(number_format((float) $f['overtime_hour_rate'], 2, ',', '.'), '0'), ',') }})</td>
                <td style="text-align:right;color:#166534;font-weight:700;">+ {{ $money($f['overtime_hour_pay']) }}</td>
            </tr>
            <tr>
                <td>Phụ cấp (hợp đồng)</td>
                <td style="text-align:right;color:#166534;font-weight:700;">+ {{ $money($f['allowance']) }}</td>
            </tr>
            <tr>
                <td>Thưởng</td>
                <td style="text-align:right;color:#166534;font-weight:700;">+ {{ $money($f['bonus']) }}</td>
            </tr>
            <tr>
                <td><strong>Tổng thu nhập</strong></td>
                <td style="text-align:right;"><strong>{{ $money($f['gross']) }}</strong></td>
            </tr>
            <tr>
                <td colspan="2" style="background:#f8fafc;font-weight:700;">Khấu trừ</td>
            </tr>
            <tr>
                <td>Bảo hiểm ({{ $money($f['insurance_base']) }} × {{ $pct }})</td>
                <td style="text-align:right;color:#dc2626;font-weight:700;">− {{ $money($f['insurance']) }}</td>
            </tr>
            <tr>
                <td>
                    Thuế TNCN
                    <div class="muted" style="font-size:12px;font-weight:400;">
                        Thu nhập chịu thuế {{ $money($f['gross']) }}
                        − BH {{ $money($f['insurance']) }}
                        − giảm trừ gia cảnh {{ $money($f['family_deduction']) }}
                        = {{ $money($f['taxable_income']) }}, rồi áp dụng biểu lũy tiến.
                    </div>
                </td>
                <td style="text-align:right;color:#dc2626;font-weight:700;">− {{ $money($f['tax']) }}</td>
            </tr>
            <tr>
                <td>Khấu trừ khác</td>
                <td style="text-align:right;color:#dc2626;font-weight:700;">− {{ $money($f['deduction']) }}</td>
            </tr>
            <tr>
                <td>Phạt đi muộn (từ chấm công)</td>
                <td style="text-align:right;color:#dc2626;font-weight:700;">− {{ $money($f['late_penalty']) }}</td>
            </tr>
            <tr>
                <td><strong>Tổng khấu trừ</strong></td>
                <td style="text-align:right;"><strong>{{ $money($f['total_deductions']) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Thực nhận</strong></td>
                <td style="text-align:right;">
                    <strong style="font-size:22px;color:var(--primary);">{{ $money($f['net']) }}</strong>
                </td>
            </tr>
        </tbody>
    </table>
    <p class="muted" style="margin:14px 0 0;font-size:12px;">
        Thực nhận = tổng thu nhập − tổng khấu trừ.
        {{ $money($f['base_salary']) }} lương hợp đồng không được cộng vào tổng thu nhập.
    </p>
</div>
@endsection
