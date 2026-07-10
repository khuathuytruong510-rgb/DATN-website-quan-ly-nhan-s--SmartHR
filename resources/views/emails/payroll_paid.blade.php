<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Thông báo thanh toán lương</title>
</head>

<body style="font-family: Arial, sans-serif;">

    <h2>Xin chào {{ $payroll->employee->name }}</h2>

    <p>Công ty đã hoàn tất thanh toán lương của bạn.</p>

    <table border="1" cellpadding="8" cellspacing="0">
        <tr>
            <td>Tháng</td>
            <td>{{ $payroll->month }}</td>
        </tr>

        <tr>
            <td>Lương cơ bản</td>
            <td>{{ number_format($payroll->base_salary) }} VNĐ</td>
        </tr>

        <tr>
            <td>Phụ cấp</td>
            <td>{{ number_format($payroll->allowance) }} VNĐ</td>
        </tr>

        <tr>
            <td>Khấu trừ</td>
            <td>{{ number_format($payroll->deduction) }} VNĐ</td>
        </tr>

        <tr>
            <td><strong>Thực nhận</strong></td>
            <td>
                <strong>{{ number_format($payroll->total_salary) }} VNĐ</strong>
            </td>
        </tr>
    </table>

    <br>

    <p>Trạng thái:
        <strong style="color:green">
            ĐÃ THANH TOÁN
        </strong>
    </p>

    <p>Cảm ơn bạn đã đồng hành cùng công ty.</p>

</body>

</html>