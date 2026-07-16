<p>Xin chào {{ $payment->employee->name ?? 'Nhân viên' }},</p>
<p>Thông báo: Lương cho tháng {{ $payment->month }}/{{ $payment->year }} đã được thanh toán.</p>
<ul>
    <li>Thực lĩnh: {{ number_format($payment->net, 2) }}</li>
    <li>Hình thức: {{ $payment->payment_method }}</li>
    <li>Ngày: {{ $payment->paid_at }}</li>
</ul>
<p>Trân trọng,</p>
<p>SmartHR</p>
