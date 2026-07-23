@extends('layouts.app')

@section('title', 'Tạo lô thanh toán mới')

@section('content')

@section('breadcrumb')
<li><a href="{{ route('payment_center.dashboard') }}">Trung tâm thanh toán</a></li>
<li><a href="{{ route('payment_center.batches.index') }}">Lô thanh toán</a></li>
<li>Tạo mới</li>
@endsection

<div class="page-head">
    <div>
        <h1>Tạo lô thanh toán mới</h1>
        <p class="muted">Chọn các phiếu lương để tạo lô thanh toán</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('payment_center.batches.index') }}">Quay lại</a>
    </div>
</div>

<form method="POST" action="{{ route('payment_center.batches.create') }}">
    @csrf

    <div class="card" style="margin-bottom:20px;">
        <h1 style="font-size:18px; margin-bottom:12px;">Thông tin lô</h1>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <div class="field">
                <label>Tên lô <span style="color:#dc2626;">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="VD: Lô thanh toán tháng 06/2026" required>
                @error('name')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
            <div class="field">
                <label>Mô tả</label>
                <input type="text" name="description" value="{{ old('description') }}" placeholder="Mô tả ngắn gọn (không bắt buộc)">
            </div>
        </div>
    </div>

    <div class="card">
        <div class="page-head" style="margin-bottom:12px;">
            <h1 style="font-size:18px;">Chọn phiếu lương</h1>
            <div class="actions">
                <button type="button" class="btn" onclick="selectAll()">Chọn tất cả</button>
                <button type="button" class="btn" onclick="deselectAll()">Bỏ chọn tất cả</button>
                <span class="muted" id="selected-count">0 phiếu được chọn</span>
            </div>
        </div>

        @error('payroll_ids')
            <div class="error" style="margin-bottom:12px;">{{ $message }}</div>
        @enderror

        @if($payrolls && $payrolls->count())
            <table>
                <thead>
                    <tr>
                        <th style="width:40px;">
                            <input type="checkbox" id="check-all" onchange="toggleAll(this)">
                        </th>
                        <th>Nhân viên</th>
                        <th>Tháng/Năm</th>
                        <th>Tổng lương</th>
                        <th>Thực lĩnh</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payrolls as $payroll)
                        <tr>
                            <td>
                                <input type="checkbox" name="payroll_ids[]" value="{{ $payroll->id }}" 
                                    {{ in_array($payroll->id, old('payroll_ids', [])) ? 'checked' : '' }}
                                    onchange="updateCount()">
                            </td>
                            <td>
                                <strong>{{ $payroll->employee->name ?? '-' }}</strong><br>
                                <small class="muted">{{ $payroll->employee->employee_code ?? '' }}</small>
                            </td>
                            <td>{{ sprintf('%02d/%04d', $payroll->month, $payroll->year) }}</td>
                            <td>{{ number_format($payroll->total_salary ?? $payroll->total ?? 0, 0) }} VNĐ</td>
                            <td><strong>{{ number_format($payroll->net_salary ?? $payroll->net ?? 0, 0) }} VNĐ</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty">Không có phiếu lương nào phù hợp để tạo lô thanh toán.</div>
        @endif
    </div>

    @if($payrolls && $payrolls->count())
        <div style="margin-top:20px; display:flex; gap:12px;">
            <button class="btn primary" type="submit" onclick="return confirm('Tạo lô thanh toán với các phiếu đã chọn?')">
                Tạo lô thanh toán
            </button>
            <a class="btn" href="{{ route('payment_center.batches.index') }}">Hủy</a>
        </div>
    @endif
</form>

@endsection

@push('scripts')
<script>
function selectAll() {
    document.querySelectorAll('input[name="payroll_ids[]"]').forEach(function(cb) { cb.checked = true; });
    updateCount();
}
function deselectAll() {
    document.querySelectorAll('input[name="payroll_ids[]"]').forEach(function(cb) { cb.checked = false; });
    updateCount();
}
function toggleAll(source) {
    document.querySelectorAll('input[name="payroll_ids[]"]').forEach(function(cb) { cb.checked = source.checked; });
    updateCount();
}
function updateCount() {
    var count = document.querySelectorAll('input[name="payroll_ids[]"]:checked').length;
    document.getElementById('selected-count').textContent = count + ' phiếu được chọn';
}
document.addEventListener('DOMContentLoaded', updateCount);
</script>
@endpush
