<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phiếu lương tháng {{ $payroll->display_month }}</title>
</head>
<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background:#f4f6fb;color:#1f2937;">
@php $m = fn ($v) => number_format((float) $v, 0, '.', ',') . ' VNĐ'; @endphp
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fb;padding:24px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="padding:24px 32px 16px;">
                            <h1 style="margin:0;font-size:24px;color:#111827;">Phiếu lương tháng {{ $payroll->display_month }}</h1>
                            <p style="margin:8px 0 0;color:#64748b;">SmartHR</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 24px;">
                            <p style="margin:0 0 16px;color:#334155;">Xin chào <strong>{{ $employee->name }}</strong>,</p>
                            <p style="margin:0 0 24px;color:#334155;">Chi tiết phiếu lương theo từng khoản thu nhập, bảo hiểm và thuế TNCN.</p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                <tr>
                                    <td style="padding:10px 0;font-weight:700;">Nhân viên</td>
                                    <td style="padding:10px 0;text-align:right;">{{ $employee->name }}</td>
                                </tr>
                                <tr style="background:#f8fafc;">
                                    <td style="padding:10px 0;font-weight:700;">Chức vụ</td>
                                    <td style="padding:10px 0;text-align:right;">{{ $employee->position }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0;font-weight:700;">Tháng lương</td>
                                    <td style="padding:10px 0;text-align:right;">{{ $payroll->display_month }}</td>
                                </tr>
                            </table>

                            <h2 style="margin:24px 0 12px;font-size:18px;">Thu nhập</h2>
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                <tr><td style="padding:8px 0;color:#475569;">Lương cơ bản HĐ</td><td style="padding:8px 0;text-align:right;">{{ $m($payroll->base_salary) }}</td></tr>
                                <tr style="background:#f8fafc;"><td style="padding:8px 0;color:#475569;">Lương theo công</td><td style="padding:8px 0;text-align:right;">{{ $m($payroll->working_salary) }}</td></tr>
                                <tr><td style="padding:8px 0;color:#475569;">Tăng ca / làm lễ·CN</td><td style="padding:8px 0;text-align:right;">{{ $m($payroll->overtime_salary) }}</td></tr>
                                <tr style="background:#f8fafc;"><td style="padding:8px 0;color:#475569;">Phụ cấp</td><td style="padding:8px 0;text-align:right;">{{ $m($payroll->allowance) }}</td></tr>
                                <tr><td style="padding:8px 0;color:#475569;">Thưởng</td><td style="padding:8px 0;text-align:right;">{{ $m($payroll->bonus) }}</td></tr>
                                <tr style="background:#eef2ff;font-weight:700;"><td style="padding:10px 0;">Tổng thu nhập</td><td style="padding:10px 0;text-align:right;">{{ $m($payroll->gross_salary ?: ((float)$payroll->working_salary + (float)$payroll->overtime_salary + (float)$payroll->allowance + (float)$payroll->bonus)) }}</td></tr>
                            </table>

                            <h2 style="margin:24px 0 12px;font-size:18px;">Bảo hiểm NLĐ</h2>
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                <tr><td style="padding:8px 0;color:#475569;">Mức đóng</td><td style="padding:8px 0;text-align:right;">{{ $m($payroll->insurance_base ?: $payroll->base_salary) }}</td></tr>
                                <tr style="background:#f8fafc;"><td style="padding:8px 0;color:#475569;">BHXH 8%</td><td style="padding:8px 0;text-align:right;">{{ $m($payroll->insurance_bhxh) }}</td></tr>
                                <tr><td style="padding:8px 0;color:#475569;">BHYT 1,5%</td><td style="padding:8px 0;text-align:right;">{{ $m($payroll->insurance_bhyt) }}</td></tr>
                                <tr style="background:#f8fafc;"><td style="padding:8px 0;color:#475569;">BHTN 1%</td><td style="padding:8px 0;text-align:right;">{{ $m($payroll->insurance_bhtn) }}</td></tr>
                                <tr style="font-weight:700;"><td style="padding:10px 0;">Tổng BH</td><td style="padding:10px 0;text-align:right;">{{ $m($payroll->insurance) }}</td></tr>
                            </table>

                            <h2 style="margin:24px 0 12px;font-size:18px;">Thuế TNCN</h2>
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                <tr><td style="padding:8px 0;color:#475569;">GTGC bản thân</td><td style="padding:8px 0;text-align:right;">{{ $m($payroll->personal_deduction_amount) }}</td></tr>
                                <tr style="background:#f8fafc;"><td style="padding:8px 0;color:#475569;">GTGC NPT ({{ (int) ($payroll->dependent_count ?? 0) }})</td><td style="padding:8px 0;text-align:right;">{{ $m($payroll->dependent_deduction_amount) }}</td></tr>
                                <tr><td style="padding:8px 0;color:#475569;">Thu nhập tính thuế</td><td style="padding:8px 0;text-align:right;">{{ $m($payroll->taxable_income) }}</td></tr>
                                <tr style="font-weight:700;"><td style="padding:10px 0;">Thuế TNCN</td><td style="padding:10px 0;text-align:right;">{{ $m($payroll->tax) }}</td></tr>
                            </table>

                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-top:16px;">
                                @if(($payroll->late_penalty_fee ?? 0) > 0)
                                <tr><td style="padding:8px 0;color:#475569;">Phạt đi muộn</td><td style="padding:8px 0;text-align:right;">{{ $m($payroll->late_penalty_fee) }}</td></tr>
                                @endif
                                <tr style="background:#eef2ff;font-weight:700;">
                                    <td style="padding:12px 0;color:#111827;">Thực nhận</td>
                                    <td style="padding:12px 0;color:#111827;text-align:right;">{{ $m($payroll->total_salary) }}</td>
                                </tr>
                            </table>

                            <p style="margin:24px 0 0;color:#475569;">Nếu có thắc mắc, vui lòng liên hệ phòng nhân sự.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
