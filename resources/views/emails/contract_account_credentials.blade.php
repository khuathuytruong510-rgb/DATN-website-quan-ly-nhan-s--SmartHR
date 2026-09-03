<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài khoản SmartHR</title>
</head>
<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background:#f4f6fb;color:#1f2937;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fb;padding:24px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="padding:24px 32px 16px;">
                            <h1 style="margin:0;font-size:22px;color:#111827;">Tài khoản đăng nhập SmartHR</h1>
                            <p style="margin:8px 0 0;color:#64748b;">Thông tin để ký hợp đồng lao động</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 24px;">
                            <p style="margin:0 0 16px;color:#334155;">Xin chào <strong>{{ $employee->name }}</strong>,</p>
                            <p style="margin:0 0 16px;color:#334155;">
                                Tài khoản đăng nhập hệ thống SmartHR đã được tạo cho bạn.
                                @if($contract)
                                    Giám đốc đã ký hợp đồng <strong>{{ $contract->contract_code }}</strong>.
                                    Vui lòng đăng nhập để xem và ký hợp đồng phía người lao động.
                                @endif
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:16px 0;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;">
                                <tr>
                                    <td style="padding:12px 16px;font-weight:700;color:#0f172a;">Email / tên đăng nhập</td>
                                    <td style="padding:12px 16px;color:#334155;text-align:right;">{{ $loginEmail }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 16px;font-weight:700;color:#0f172a;">Mật khẩu mặc định</td>
                                    <td style="padding:12px 16px;color:#334155;text-align:right;"><strong>{{ $plainPassword }}</strong></td>
                                </tr>
                            </table>

                            <p style="margin:0 0 8px;color:#334155;"><strong>Bước tiếp theo:</strong></p>
                            <ol style="margin:0 0 16px;padding-left:20px;color:#334155;line-height:1.7;">
                                <li>Đăng nhập bằng email và mật khẩu ở trên.</li>
                                <li>Vào mục <strong>Hợp đồng của tôi</strong>.</li>
                                <li>Mở hợp đồng chờ ký và bấm <strong>Ký</strong> phía người lao động.</li>
                            </ol>

                            <p style="margin:0;color:#64748b;font-size:13px;">
                                Vì lý do bảo mật, hãy đổi mật khẩu sau khi đăng nhập lần đầu (nếu hệ thống hỗ trợ).
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
