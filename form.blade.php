@extends('layouts.app')

@section('content')
<div class="container mt-5" style="max-width: 600px;">
    <div class="card border-0 shadow">
        <div class="card-header bg-primary text-white text-center py-3">
            <h4 class="mb-0 fw-bold">📝 ĐƠN ĐĂNG KÝ NGHỈ PHÉP</h4>
        </div>
        <div class="card-body p-4">
            {{-- Hiển thị thông báo lỗi nếu Validation ở Controller thất bại --}}
            @if ($errors->any())
                <div class="alert alert-danger py-2 alert-dismissible fade show" role="alert">
                    <ul class="mb-0 small">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form action="{{ route('leave_requests.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-bold small">Nhân viên làm đơn:</label>
                    <select name="employee_id" class="form-select" required>
                        <option value="">-- Chọn nhân viên tạo đơn --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                                {{ $emp->name }} (Mã ID: {{ $emp->id }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Loại phép xin nghỉ:</label>
                    <select name="type" class="form-select" required>
                        <option value="annual">Nghỉ phép năm</option>
                        <option value="sick">Nghỉ ốm</option>
                        <option value="personal">Nghỉ cá nhân</option>
                        <option value="unpaid">Nghỉ không lương</option>
                    </select>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-bold small">Nghỉ từ ngày:</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="{{ old('start_date') }}" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold small">Đến hết ngày:</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="{{ old('end_date') }}" required>
                    </div>
                </div>

                <div class="mb-3 bg-light p-3 rounded border text-center">
                    <span class="text-muted small d-block">Tổng số ngày nghỉ dự kiến:</span>
                    <h3 class="mb-0 fw-bold text-success" id="display_days">0 Ngày</h3>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold small">Lý do xin nghỉ phép:</label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="Nhập lý do chi tiết..." required>{{ old('reason') }}</textarea>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary py-2.5 fw-bold fs-5">📤 GỬI ĐƠN XIN NGHỈ</button>
                    <a href="{{ route('leave_requests.index') }}" class="btn btn-outline-secondary py-2">Quay lại danh sách</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const displayDays = document.getElementById('display_days');

    function calculateLeaveDays() {
        const startVal = startDateInput.value;
        const endVal = endDateInput.value;

        if (startVal && endVal) {
            const start = new Date(startVal);
            const end = new Date(endVal);

            // Kiểm tra tính hợp lệ của mốc thời gian
            if (end < start) {
                displayDays.innerText = "Ngày không hợp lệ!";
                displayDays.classList.replace('text-success', 'text-danger');
                return;
            }

            // Tính toán số ngày thực tế bằng cách đổi milisecond sang ngày (Cộng 1 để tính cả ngày bắt đầu)
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; 

            displayDays.innerText = diffDays + " Ngày";
            displayDays.classList.replace('text-danger', 'text-success');
        } else {
            displayDays.innerText = "0 Ngày";
        }
    }

    // Tự động tính toán lại số ngày nếu trang bị reload do lỗi validation dữ liệu cũ
    document.addEventListener("DOMContentLoaded", calculateLeaveDays);

    // Lắng nghe sự kiện thay đổi dữ liệu trên lịch chọn ngày
    startDateInput.addEventListener('change', calculateLeaveDays);
    endDateInput.addEventListener('change', calculateLeaveDays);
</script>
@endsection