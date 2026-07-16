<p>Xin chào {{ $advance->employee->name ?? 'Nhân viên' }},</p>
<p>Yêu cầu ứng lương của bạn đã được duyệt.</p>
<ul>
    <li>Số tiền: {{ number_format($advance->amount, 2) }}</li>
    <li>Ngày duyệt: {{ $advance->approved_at }}</li>
    <li>Ghi chú: {{ $advance->notes }}</li>
</ul>
<p>Trân trọng,</p>
<p>SmartHR</p>
