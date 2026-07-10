<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Thông báo bảng lương</title>
</head>

<body>

    <h2>Xin chào {{ $payroll->employee->name }}</h2>

    <p>
        Bảng lương tháng
        <strong>{{ $payroll->month }}</strong>
        đã được HR chốt.
    </p>

    <hr>

    <p>Lương cơ bản:
        {{ number_format($payroll->base_salary) }} VNĐ
    </p>

    <p>Phụ cấp:
        {{ number_format($payroll->allowance) }} VNĐ
    </p>

    <p>Khấu trừ:
        {{ number_format($payroll->deduction) }} VNĐ
    </p>

    <h3>
        Tổng lương:
        {{ number_format($payroll->total_salary) }} VNĐ
    </h3>

    <br>

    <a href="{{ route('payroll.confirm', $payroll->confirm_token) }}"
   style="
        display:inline-block;
        padding:12px 20px;
        background:#2563eb;
        color:white;
        text-decoration:none;
        border-radius:6px;">
    XÁC NHẬN BẢNG LƯƠNG
</a>

</body>

</html>