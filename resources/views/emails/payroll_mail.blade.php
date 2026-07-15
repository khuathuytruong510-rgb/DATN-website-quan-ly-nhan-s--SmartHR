<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phiếu lương tháng {{ $payroll->display_month }}</title>
</head>
<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background:#f4f6fb;color:#1f2937;">
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
                            <p style="margin:0 0 24px;color:#334155;">Đây là chi tiết phiếu lương của bạn. Vui lòng kiểm tra kỹ thông tin trước khi thanh toán.</p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                <tr>
                                    <td style="padding:10px 0;font-weight:700;color:#0f172a;">Công ty</td>
                                    <td style="padding:10px 0;color:#334155;text-align:right;">SmartHR</td>
                                </tr>
                                <tr style="background:#f8fafc;">
                                    <td style="padding:10px 0;font-weight:700;color:#0f172a;">Nhân viên</td>
                                    <td style="padding:10px 0;color:#334155;text-align:right;">{{ $employee->name }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0;font-weight:700;color:#0f172a;">Chức vụ</td>
                                    <td style="padding:10px 0;color:#334155;text-align:right;">{{ $employee->position }}</td>
                                </tr>
                                <tr style="background:#f8fafc;">
                                    <td style="padding:10px 0;font-weight:700;color:#0f172a;">Tháng lương</td>
                                    <td style="padding:10px 0;color:#334155;text-align:right;">{{ $payroll->display_month }}</td>
                                </tr>
                            </table>

                            <h2 style="margin:24px 0 12px;font-size:18px;color:#111827;">Chi tiết lương</h2>
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                <tr>
                                    <td style="padding:10px 0;border-top:1px solid #e5e7eb;color:#475569;">Lương cơ bản</td>
                                    <td style="padding:10px 0;border-top:1px solid #e5e7eb;color:#0f172a;text-align:right;">{{ number_format($payroll->base_salary, 0, '.', ',') }} VNĐ</td>
                                </tr>
                                <tr style="background:#f8fafc;">
                                    <td style="padding:10px 0;color:#475569;">Công</td>
                                    <td style="padding:10px 0;color:#0f172a;text-align:right;">{{ $payroll->working_days ?? 0 }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0;color:#475569;">Tăng ca</td>
                                    <td style="padding:10px 0;color:#0f172a;text-align:right;">{{ number_format($payroll->overtime_hours ?? 0, 2, '.', ',') }} giờ</td>
                                </tr>
                                <tr style="background:#f8fafc;">
                                    <td style="padding:10px 0;color:#475569;">Phụ cấp</td>
                                    <td style="padding:10px 0;color:#0f172a;text-align:right;">{{ number_format($payroll->allowance ?? 0, 0, '.', ',') }} VNĐ</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0;color:#475569;">Thưởng</td>
                                    <td style="padding:10px 0;color:#0f172a;text-align:right;">{{ number_format($payroll->bonus ?? 0, 0, '.', ',') }} VNĐ</td>
                                </tr>
                                <tr style="background:#f8fafc;">
                                    <td style="padding:10px 0;color:#475569;">Bảo hiểm</td>
                                    <td style="padding:10px 0;color:#0f172a;text-align:right;">{{ number_format($payroll->insurance ?? 0, 0, '.', ',') }} VNĐ</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0;color:#475569;">Thuế thu nhập cá nhân</td>
                                    <td style="padding:10px 0;color:#0f172a;text-align:right;">{{ number_format($payroll->tax ?? 0, 0, '.', ',') }} VNĐ</td>
                                </tr>
                                <tr style="background:#eef2ff; font-weight:700;">
                                    <td style="padding:12px 0;color:#111827;">Thực nhận</td>
                                    <td style="padding:12px 0;color:#111827;text-align:right;">{{ number_format($payroll->total_salary ?? 0, 0, '.', ',') }} VNĐ</td>
                                </tr>
                            </table>

                            <p style="margin:24px 0 0;color:#475569;">Nếu có thắc mắc, vui lòng liên hệ phòng nhân sự để được hỗ trợ thêm.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
