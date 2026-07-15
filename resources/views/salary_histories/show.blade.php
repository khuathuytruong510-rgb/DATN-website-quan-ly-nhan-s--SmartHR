@extends('layouts.app')

@section('title', 'Chi tiết lịch sử lương')

@section('content')
<div class="page-head">
    <div>
        <h1>Chi tiết lịch sử lương</h1>
        <p class="muted">Thông tin chi tiết thay đổi lương của nhân viên.</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ url()->previous() }}">Quay lại</a>
        <a class="btn" href="#">Sửa</a>
        <a class="btn" target="_blank" href="#">In</a>
        <a class="btn primary" target="_blank" href="#">Xuất PDF</a>
    </div>
</div>

<div class="card">
    <div class="grid two-cols">
        <div>
            <h3>Thông tin nhân viên</h3>
            <div class="field"><label>Ảnh đại diện</label>
                <div>
                    @if(optional($salaryHistory->employee)->avatar)
                        <img src="{{ asset('storage/' . $salaryHistory->employee->avatar) }}" alt="avatar" style="width:96px;height:96px;border-radius:8px;object-fit:cover">
                    @else
                        <div class="empty">Chưa có ảnh</div>
                    @endif
                </div>
            </div>
            <div class="field"><label>Mã nhân viên</label><div>{{ optional($salaryHistory->employee)->employee_code ?? 'Chưa có dữ liệu' }}</div></div>
            <div class="field"><label>Họ và tên</label><div>{{ optional($salaryHistory->employee)->name ?? 'Chưa có dữ liệu' }}</div></div>
            <div class="field"><label>Email</label><div>{{ optional($salaryHistory->employee)->email ?? 'Chưa có dữ liệu' }}</div></div>
            <div class="field"><label>Số điện thoại</label><div>{{ optional($salaryHistory->employee)->phone ?? 'Chưa có dữ liệu' }}</div></div>
            <div class="field"><label>Phòng ban</label><div>{{ optional($salaryHistory->employee->department)->name ?? ($salaryHistory->department_id ? 'Chưa có tên phòng ban' : 'Chưa có dữ liệu') }}</div></div>
            <div class="field"><label>Chức vụ</label><div>{{ $salaryHistory->position ?? optional($salaryHistory->employee)->position ?? 'Chưa có dữ liệu' }}</div></div>
        </div>

        <div>
            <h3>Thông tin lịch sử lương</h3>
            <div class="field"><label>Mã</label><div>{{ $salaryHistory->code ?? ('SH' . $salaryHistory->id) }}</div></div>
            <div class="field"><label>Kỳ lương</label><div>{{ $salaryHistory->period ?? 'Chưa có' }}</div></div>
            <div class="field"><label>Ngày áp dụng</label><div>{{ $salaryHistory->effective_date?->format('Y-m-d') ?? 'Chưa có' }}</div></div>
            <div class="field"><label>Loại thay đổi</label><div>{{ ucfirst($salaryHistory->change_type ?? 'Điều chỉnh') }}</div></div>

            <div class="row">
                <div class="col-md-6">
                    <div class="field"><label>Mức lương cũ</label><div>{{ number_format($old, 0, ',', '.') }} VNĐ</div></div>
                </div>
                <div class="col-md-6">
                    <div class="field"><label>Mức lương mới</label><div>{{ number_format($new, 0, ',', '.') }} VNĐ</div></div>
                </div>
            </div>

            <div class="field"><label>Chênh lệch</label><div>{{ number_format($difference, 0, ',', '.') }} VNĐ ({{ $percent !== null ? $percent . '%' : 'n/a' }})</div></div>

            <div class="field"><label>Trạng thái</label>
                <div>
                    @php
                        $status = $salaryHistory->status ?? 'pending';
                        $badge = 'badge-secondary';
                        if ($status === 'applied' || $status === 'Áp dụng' || $status === 'Áp dụng') $badge = 'badge-success';
                        if ($status === 'pending' || $status === 'chờ') $badge = 'badge-warning';
                        if ($status === 'cancelled' || $status === 'hủy') $badge = 'badge-danger';
                    @endphp
                    <span class="badge {{ $badge }}">{{ ucfirst($status) }}</span>
                </div>
            </div>

            <div class="field"><label>Người cập nhật</label><div>{{ optional($salaryHistory->updatedBy)->name ?? 'Chưa có dữ liệu' }}</div></div>
            <div class="field"><label>Thời gian cập nhật</label><div>{{ $salaryHistory->updated_at?->format('Y-m-d H:i') ?? 'Chưa có dữ liệu' }}</div></div>

            <h4>Phụ cấp</h4>
            <table>
                <tr><th>Phụ cấp chức vụ</th><td>{{ number_format($allowances['position'] ?? 0, 0, ',', '.') }} VNĐ</td></tr>
                <tr><th>Phụ cấp trách nhiệm</th><td>{{ number_format($allowances['responsibility'] ?? 0, 0, ',', '.') }} VNĐ</td></tr>
                <tr><th>Phụ cấp ăn trưa</th><td>{{ number_format($allowances['lunch'] ?? 0, 0, ',', '.') }} VNĐ</td></tr>
                <tr><th>Phụ cấp xăng xe</th><td>{{ number_format($allowances['transport'] ?? 0, 0, ',', '.') }} VNĐ</td></tr>
                <tr><th>Phụ cấp khác</th><td>{{ number_format($allowances['other'] ?? 0, 0, ',', '.') }} VNĐ</td></tr>
                <tr><th><strong>Tổng phụ cấp</strong></th><td><strong>{{ number_format($allowanceTotal, 0, ',', '.') }} VNĐ</strong></td></tr>
            </table>

            <h4>Thưởng & Khấu trừ</h4>
            <table>
                <tr><th>Thưởng</th><td>{{ number_format($rewards, 0, ',', '.') }} VNĐ</td></tr>
                <tr><th>Khấu trừ</th><td>{{ number_format($deductions, 0, ',', '.') }} VNĐ</td></tr>
                <tr><th>Thuế</th><td>{{ number_format($tax, 0, ',', '.') }} VNĐ</td></tr>
                <tr><th>Bảo hiểm</th><td>{{ number_format($insurance, 0, ',', '.') }} VNĐ</td></tr>
                <tr><th><strong>Thực nhận sau điều chỉnh</strong></th><td><strong>{{ number_format($net, 0, ',', '.') }} VNĐ</strong></td></tr>
            </table>

            <h4>Lý do thay đổi</h4>
            <div class="field"><label>Nội dung thay đổi</label><div>{{ $salaryHistory->notes ?? 'Chưa có dữ liệu' }}</div></div>
            <div class="field"><label>Ghi chú</label><div class="muted">{{ $salaryHistory->notes ?? 'Chưa có dữ liệu' }}</div></div>
            <div class="field"><label>Quyết định / Số văn bản</label><div>{{ $salaryHistory->document_number ?? 'Chưa có dữ liệu' }}</div></div>
        </div>
    </div>
</div>

@endsection
