<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Xác nhận lương</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; background: #f8fafc; margin: 0; padding: 0;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);">
        <tr style="background: #2563eb; color: #ffffff;">
            <td style="padding: 24px; text-align: center;">
                <h1 style="margin: 0; font-size: 24px;">Xác nhận lương</h1>
                <p style="margin: 8px 0 0; color: rgba(255,255,255,0.8);">SmartHR</p>
            </td>
        </tr>
        <tr>
            <td style="padding: 24px;">
                <p>Xin chào {{ optional($employee)->name ?? 'nhân viên' }},</p>
                @if(!empty($isRevision))
                    <p>Bảng lương tháng <strong>{{ $payroll->display_month }}</strong> đã được <strong>chỉnh sửa</strong> sau báo cáo sự cố của bạn. Vui lòng kiểm tra lại số liệu và xác nhận.</p>
                @else
                    <p>Đây là thông tin lương của bạn cho tháng <strong>{{ $payroll->display_month }}</strong>. Vui lòng kiểm tra và xác nhận trước khi thanh toán.</p>
                @endif

                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin-top: 20px;">
                    <tr>
                        <td style="padding: 12px; border: 1px solid #e5e7eb; background: #f8fafc;"><strong>Lương cơ bản</strong></td>
                        <td style="padding: 12px; border: 1px solid #e5e7eb; text-align: right;">{{ number_format($payroll->base_salary, 0, '.', ',') }} VNĐ</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; border: 1px solid #e5e7eb; background: #f8fafc;"><strong>Lương công</strong></td>
                        <td style="padding: 12px; border: 1px solid #e5e7eb; text-align: right;">{{ number_format($payroll->working_salary ?? 0, 0, '.', ',') }} VNĐ</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; border: 1px solid #e5e7eb; background: #f8fafc;"><strong>Phụ cấp</strong></td>
                        <td style="padding: 12px; border: 1px solid #e5e7eb; text-align: right;">{{ number_format($payroll->allowance ?? 0, 0, '.', ',') }} VNĐ</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; border: 1px solid #e5e7eb; background: #f8fafc;"><strong>Thưởng</strong></td>
                        <td style="padding: 12px; border: 1px solid #e5e7eb; text-align: right;">{{ number_format($payroll->bonus ?? 0, 0, '.', ',') }} VNĐ</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; border: 1px solid #e5e7eb; background: #f8fafc;"><strong>Khấu trừ</strong></td>
                        <td style="padding: 12px; border: 1px solid #e5e7eb; text-align: right;">{{ number_format($payroll->deduction ?? 0, 0, '.', ',') }} VNĐ</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; border: 1px solid #e5e7eb; background: #f8fafc;"><strong>Bảo hiểm</strong></td>
                        <td style="padding: 12px; border: 1px solid #e5e7eb; text-align: right;">{{ number_format($payroll->insurance ?? 0, 0, '.', ',') }} VNĐ</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; border: 1px solid #e5e7eb; background: #f8fafc;"><strong>Thuế</strong></td>
                        <td style="padding: 12px; border: 1px solid #e5e7eb; text-align: right;">{{ number_format($payroll->tax ?? 0, 0, '.', ',') }} VNĐ</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; border: 1px solid #e5e7eb; background: #e0f2fe;"><strong>Thực nhận</strong></td>
                        <td style="padding: 12px; border: 1px solid #e5e7eb; text-align: right; font-weight: 700;">{{ number_format($payroll->total_salary ?? 0, 0, '.', ',') }} VNĐ</td>
                    </tr>
                </table>

                <p style="margin-top: 24px;">Nếu thông tin chính xác, vui lòng bấm nút bên dưới để xác nhận (hoặc xác nhận trên trang Lương của bạn). Nếu không phản hồi sau 3 ngày, hệ thống sẽ tự động chuyển sang bước thanh toán.</p>

                @if(!empty($confirmUrl))
                    <p style="text-align:center; margin: 28px 0;">
                        <a href="{{ $confirmUrl }}" style="display:inline-block; background:#2563eb; color:#fff; text-decoration:none; padding:12px 22px; border-radius:8px; font-weight:700;">
                            Xác nhận bảng lương
                        </a>
                    </p>
                @endif

                <p style="margin-bottom: 0;">Trân trọng,<br>Đội ngũ SmartHR</p>
            </td>
        </tr>
    </table>
</body>
</html>
