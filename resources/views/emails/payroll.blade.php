<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; max-width: 500px; }
        td { padding: 8px; }
    </style>
</head>

<body>
<h2 style="color:#2563eb">
    SMART HR
</h2>

<h3>Thông báo chốt lương</h3>

<p>
Bộ phận nhân sự đã hoàn tất việc chốt lương của bạn.
Dưới đây là thông tin bảng lương:
</p>

<p>Bảng lương tháng <b>{{ $payroll->month }}</b></p>

<table
style="
width:100%;
max-width:600px;
border-collapse:collapse;
border:1px solid #ddd;
">

<tr>
    <td>Lương cơ bản</td>
    <td>{{ number_format($payroll->base_salary ?? 0) }} VNĐ</td>
</tr>

<tr>
    <td>Phụ cấp</td>
    <td>{{ number_format($payroll->allowance ?? 0) }} VNĐ</td>
</tr>

<tr>
    <td>Khấu trừ</td>
    <td>{{ number_format($payroll->deduction ?? 0) }} VNĐ</td>
</tr>

<tr style="background:#e8f5e9">
    <td><b>Thực nhận</b></td>
    <td>
        <b style="color:green">
            {{ number_format($payroll->total_salary ?? 0) }} VNĐ
        </b>
    </td>
</tr>

</table>

<p>Cảm ơn bạn đã làm việc tại SmartHR.</p>
<p>
Ngày gửi:
<b>{{ now()->format('d/m/Y H:i') }}</b>
</p>

<hr>

<p>

Phòng Nhân sự

<br>

SMART HR

</p>

</body>
</html>