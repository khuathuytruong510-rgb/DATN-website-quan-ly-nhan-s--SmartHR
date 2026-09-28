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
    $pct = fn ($rate) => number_format(((float) $rate) * 100, 1, ',', '.') . '%';
@endphp

<div class="page-head">
    <div>
        <h1>Chi tiết bảng lương</h1>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('accountant.payroll.index') }}">Quay lại</a>
        @if($workflow->isDirectorApproved($payroll->status) || in_array($payroll->status, \App\Services\PayrollPaymentWorkflowService::completedStatuses(), true))
        <form method="POST" action="{{ route('accountant.payroll.send_email', $payroll) }}" style="display:inline;">
            @csrf
            <button class="btn" type="submit">Gửi lại email thông báo</button>
        </form>
        @endif
        @if(in_array($payroll->status, \App\Services\PayrollPaymentWorkflowService::recalculableStatuses(), true))
        <form method="POST" action="{{ route('accountant.payroll.recalculate', $payroll) }}" style="display:inline;">
            @csrf
            <button class="btn" type="submit">Tính lại</button>
        </form>
        @endif
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:16px;">
    <div class="card" style="margin:0;">
        <p style="margin:0;font-size:13px;color:#64748b;">Lương cơ bản (HĐ)</p>
        <p style="margin:0;font-size:22px;font-weight:800;">{{ $money($f['base_salary']) }}</p>
    </div>
    <div class="card" style="margin:0;">
        <p style="margin:0;font-size:13px;color:#64748b;">Tổng thu nhập</p>
        <p style="margin:0;font-size:22px;font-weight:800;">{{ $money($f['gross']) }}</p>
    </div>
    <div class="card" style="margin:0;">
        <p style="margin:0;font-size:13px;color:#64748b;">Thực nhận</p>
        <p style="margin:0;font-size:22px;font-weight:800;color:var(--primary);">{{ $money($f['net']) }}</p>
    </div>
</div>

<div class="grid two-cols">
    <div class="card">
        <h3 style="margin-top:0;">Thông tin phiếu</h3>
        <div style="margin-bottom:14px;">
            <span class="muted" style="font-size:13px;">Nhân viên</span>
            <p style="margin:4px 0 0;font-weight:700;">{{ optional($employee)->name }}</p>
        </div>
        <div style="margin-bottom:14px;">
            <span class="muted" style="font-size:13px;">Chức vụ</span>
            <p style="margin:4px 0 0;font-weight:600;">{{ optional($employee)->position ?: '—' }}</p>
        </div>
        <div style="margin-bottom:14px;">
            <span class="muted" style="font-size:13px;">Kỳ lương</span>
            <p style="margin:4px 0 0;font-weight:600;">Tháng {{ $payroll->display_month }}</p>
        </div>
        <div style="margin-bottom:14px;">
            <span class="muted" style="font-size:13px;">Người phụ thuộc (GTGC)</span>
            <p style="margin:4px 0 0;font-weight:600;">{{ (int) $f['dependent_count'] }} người</p>
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
                    <td>Tăng ca giờ (ngày thường)</td>
                    <td style="text-align:right;font-weight:700;">{{ number_format((float) ($f['overtime_hours'] ?? 0), 2) }} giờ</td>
                </tr>
                <tr>
                    <td>Lương ngày / giờ</td>
                    <td style="text-align:right;font-weight:700;">{{ $money($f['daily_salary']) }} / {{ $money($f['hour_salary']) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="margin-top:16px;">
    <h3 style="margin-top:0;">1) Thu nhập theo từng mục</h3>
    <table>
        <thead>
            <tr>
                <th>Khoản mục</th>
                <th style="text-align:right;">Số tiền</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Lương cơ bản HĐ (cơ sở tính ngày công / mức đóng BH)</td>
                <td style="text-align:right;color:#64748b;">{{ $money($f['base_salary']) }}</td>
            </tr>
            <tr>
                <td>Lương đi làm ({{ $f['work_days'] }} × {{ $money($f['daily_salary']) }})</td>
                <td style="text-align:right;color:#166534;font-weight:700;">+ {{ $money($f['work_pay']) }}</td>
            </tr>
            <tr>
                <td>Lương nghỉ phép có lương ({{ $f['paid_leave_days'] }} × {{ $money($f['daily_salary']) }})</td>
                <td style="text-align:right;color:#166534;font-weight:700;">+ {{ $money($f['leave_pay']) }}</td>
            </tr>
            <tr>
                <td>Lương ngày lễ hưởng lương ({{ $f['paid_holiday_days'] }} × 100%)</td>
                <td style="text-align:right;color:#166534;font-weight:700;">+ {{ $money($f['holiday_pay']) }}</td>
            </tr>
            <tr>
                <td>Đi làm ngày lễ — Điều 98 BLLĐ (× {{ number_format((float) $f['holiday_work_rate'], 1) }}, chưa kể 100% ngày lễ)</td>
                <td style="text-align:right;color:#166534;font-weight:700;">+ {{ $money($f['holiday_work_pay']) }}</td>
            </tr>
            <tr>
                <td>Đi làm Chủ nhật — Điều 98 (× {{ number_format((float) $f['weekly_rest_rate'], 1) }})</td>
                <td style="text-align:right;color:#166534;font-weight:700;">+ {{ $money($f['weekly_rest_pay']) }}</td>
            </tr>
            <tr>
                <td>
                    Tăng ca giờ ngày thường (× {{ number_format((float) $f['overtime_hour_rate'], 1) }})
                    <div class="muted" style="font-size:12px;">{{ number_format((float) ($f['overtime_hours'] ?? 0), 2) }} giờ × {{ $money($f['hour_salary']) }} × {{ number_format((float) $f['overtime_hour_rate'], 1) }}</div>
                </td>
                <td style="text-align:right;color:#166534;font-weight:700;">+ {{ $money($f['overtime_hour_pay']) }}</td>
            </tr>
            <tr>
                <td>Phụ cấp hợp đồng</td>
                <td style="text-align:right;color:#166534;font-weight:700;">+ {{ $money($f['allowance']) }}</td>
            </tr>
            <tr>
                <td>Thưởng chuyên cần / khác</td>
                <td style="text-align:right;color:#166534;font-weight:700;">+ {{ $money($f['bonus']) }}</td>
            </tr>
            <tr>
                <td><strong>Tổng thu nhập (Gross)</strong></td>
                <td style="text-align:right;"><strong>{{ $money($f['gross']) }}</strong></td>
            </tr>
        </tbody>
    </table>
</div>

<div class="card" style="margin-top:16px;">
    <h3 style="margin-top:0;">2) Bảo hiểm bắt buộc người lao động đóng</h3>
    <table>
        <thead>
            <tr>
                <th>Khoản</th>
                <th>Mức đóng / trần</th>
                <th style="text-align:right;">Tỷ lệ</th>
                <th style="text-align:right;">Số tiền</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Mức lương làm căn cứ đóng</td>
                <td colspan="2">Lương cơ bản HĐ</td>
                <td style="text-align:right;font-weight:700;">{{ $money($f['insurance_base']) }}</td>
            </tr>
            <tr>
                <td>BHXH (hưu trí + tử tuất)</td>
                <td>min(mức đóng, {{ $money($f['insurance_bhxh_bhyt_cap']) }}) = {{ $money($f['insurance_bhxh_bhyt_base']) }}</td>
                <td style="text-align:right;">{{ $pct($f['insurance_bhxh_rate']) }}</td>
                <td style="text-align:right;color:#dc2626;font-weight:700;">− {{ $money($f['insurance_bhxh']) }}</td>
            </tr>
            <tr>
                <td>BHYT</td>
                <td>min(mức đóng, {{ $money($f['insurance_bhxh_bhyt_cap']) }}) = {{ $money($f['insurance_bhxh_bhyt_base']) }}</td>
                <td style="text-align:right;">{{ $pct($f['insurance_bhyt_rate']) }}</td>
                <td style="text-align:right;color:#dc2626;font-weight:700;">− {{ $money($f['insurance_bhyt']) }}</td>
            </tr>
            <tr>
                <td>BHTN</td>
                <td>min(mức đóng, {{ $money($f['insurance_bhtn_cap']) }}) = {{ $money($f['insurance_bhtn_base']) }}</td>
                <td style="text-align:right;">{{ $pct($f['insurance_bhtn_rate']) }}</td>
                <td style="text-align:right;color:#dc2626;font-weight:700;">− {{ $money($f['insurance_bhtn']) }}</td>
            </tr>
            <tr>
                <td colspan="3"><strong>Tổng BH NLĐ khấu trừ</strong></td>
                <td style="text-align:right;"><strong>− {{ $money($f['insurance']) }}</strong></td>
            </tr>
            <tr>
                <td colspan="4" style="background:#f8fafc;font-size:12px;color:#64748b;">
                    Phần công ty đóng (không trừ lương): BHXH {{ $money($f['employer_bhxh']) }}
                    + BHYT {{ $money($f['employer_bhyt']) }}
                    + BHTN {{ $money($f['employer_bhtn']) }}
                    = {{ $money($f['employer_insurance_total']) }}.
                </td>
            </tr>
        </tbody>
    </table>
</div>

<div class="card" style="margin-top:16px;">
    <h3 style="margin-top:0;">3) Thuế TNCN (biểu lũy tiến từng phần)</h3>
    <table>
        <tbody>
            <tr>
                <td>Tổng thu nhập chịu thuế</td>
                <td style="text-align:right;font-weight:700;">{{ $money($f['gross']) }}</td>
            </tr>
            <tr>
                <td>− Bảo hiểm bắt buộc NLĐ</td>
                <td style="text-align:right;color:#dc2626;font-weight:700;">− {{ $money($f['insurance']) }}</td>
            </tr>
            <tr>
                <td>− Giảm trừ bản thân (NQ 954/2020)</td>
                <td style="text-align:right;color:#dc2626;font-weight:700;">− {{ $money($f['personal_deduction']) }}</td>
            </tr>
            <tr>
                <td>− Giảm trừ người phụ thuộc ({{ (int) $f['dependent_count'] }} × {{ $money($f['dependent_deduction_unit'] ?? 0) }})</td>
                <td style="text-align:right;color:#dc2626;font-weight:700;">− {{ $money($f['dependent_deduction']) }}</td>
            </tr>
            <tr>
                <td><strong>Thu nhập tính thuế (TNTT)</strong></td>
                <td style="text-align:right;"><strong>{{ $money($f['taxable_income']) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <h4 style="margin:18px 0 8px;">Chi tiết từng bậc thuế</h4>
    <table>
        <thead>
            <tr>
                <th>Bậc</th>
                <th>Khoảng TNTT</th>
                <th style="text-align:right;">Thuế suất</th>
                <th style="text-align:right;">TNTT trong bậc</th>
                <th style="text-align:right;">Thuế</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($f['tax_segments'] ?? []) as $segment)
            <tr>
                <td>Bậc {{ $segment['bracket'] }}</td>
                <td>
                    {{ $money($segment['from']) }}
                    →
                    {{ $segment['to'] === null ? 'trở lên' : $money($segment['to']) }}
                </td>
                <td style="text-align:right;">{{ $pct($segment['rate']) }}</td>
                <td style="text-align:right;">{{ $money($segment['taxable_in_bracket']) }}</td>
                <td style="text-align:right;color:#dc2626;font-weight:700;">{{ $money($segment['tax']) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="color:#64748b;">TNTT ≤ 0 — không phát sinh thuế.</td>
            </tr>
            @endforelse
            <tr>
                <td colspan="4"><strong>Tổng thuế TNCN khấu trừ</strong></td>
                <td style="text-align:right;"><strong>− {{ $money($f['tax']) }}</strong></td>
            </tr>
        </tbody>
    </table>
</div>

<div class="card" style="margin-top:16px;">
    <h3 style="margin-top:0;">4) Khấu trừ khác & thực nhận</h3>
    <table>
        <tbody>
            <tr>
                <td>Khấu trừ khác</td>
                <td style="text-align:right;color:#dc2626;font-weight:700;">− {{ $money($f['deduction']) }}</td>
            </tr>
            <tr>
                <td>Phạt đi muộn (từ chấm công)</td>
                <td style="text-align:right;color:#dc2626;font-weight:700;">− {{ $money($f['late_penalty']) }}</td>
            </tr>
            <tr>
                <td><strong>Tổng khấu trừ (BH + thuế + khác + phạt)</strong></td>
                <td style="text-align:right;"><strong>− {{ $money($f['total_deductions']) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Thực nhận = Gross − tổng khấu trừ</strong></td>
                <td style="text-align:right;">
                    <strong style="font-size:22px;color:var(--primary);">{{ $money($f['net']) }}</strong>
                </td>
            </tr>
        </tbody>
    </table>
</div>
@endsection
