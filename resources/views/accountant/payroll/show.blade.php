@extends('layouts.app')

@section('title', 'Chi tiết bảng lương')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('accountant.dashboard') }}">Kế toán</a></li>
<li><a href="{{ route('accountant.payroll.index') }}">Quản lý bảng lương</a></li>
<li>Chi tiết</li>
@endsection

<div class="page-head">
    <div>
        <h1><i class="bi bi-receipt me-2"></i>Chi tiết bảng lương</h1>
        <p class="muted">Bảng lương cho {{ optional($payroll->employee)->name }}</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('accountant.payroll.index') }}"><i class="bi bi-arrow-left me-1"></i> Quay lại</a>
        <button class="btn" type="button" onclick="confirmSendEmail()">
            <i class="bi bi-envelope me-1"></i> Gửi email
        </button>
        <button class="btn" type="button" onclick="confirmRecalculate()">
            <i class="bi bi-arrow-clockwise me-1"></i> Tính lại
        </button>
        @if(!$payroll->locked)
            <button class="btn btn-danger" type="button" onclick="confirmLock()">
                <i class="bi bi-lock me-1"></i> Khoá
            </button>
        @else
            <button class="btn btn-success" type="button" onclick="confirmUnlock()">
                <i class="bi bi-unlock me-1"></i> Mở khoá
            </button>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="mb-3">
                    <span class="text-muted small">Tháng:</span>
                    <p class="fw-bold mb-0">{{ sprintf('%02d', $payroll->month) }}/{{ $payroll->year }}</p>
                </div>
                <div class="mb-3">
                    <span class="text-muted small">Nhân viên:</span>
                    <p class="fw-bold mb-0">{{ optional($payroll->employee)->name }} ({{ optional($payroll->employee)->email }})</p>
                </div>
                <div class="mb-3">
                    <span class="text-muted small">Lương cơ bản:</span>
                    <p class="fw-bold mb-0">{{ number_format($payroll->base_salary ?? 0, 0, '.', ',') }} VNĐ</p>
                </div>
                <div class="mb-3">
                    <span class="text-muted small">Ngày công:</span>
                    <p class="fw-bold mb-0">{{ $payroll->working_days ?? 0 }}/{{ $payroll->required_working_days ?? 26 }} ngày</p>
                </div>
                @if(($payroll->paid_leave_days ?? 0) > 0 || ($payroll->unpaid_leave_days ?? 0) > 0)
                <div class="mb-3">
                    <span class="text-muted small">Nghỉ phép:</span>
                    <p class="mb-0">
                        @if(($payroll->paid_leave_days ?? 0) > 0)<span class="text-success">{{ number_format($payroll->paid_leave_days, 1) }} / {{ number_format($payroll->paid_leave_salary, 0, '.', ',') }}</span>@endif
                        @if(($payroll->unpaid_leave_days ?? 0) > 0) <span class="text-danger">{{ number_format($payroll->unpaid_leave_days, 1) }}</span>@endif
                    </p>
                </div>
                @endif
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <span class="text-muted small">Trạng thái:</span>
                    <p class="mb-0">
                        @if($payroll->status === 'pending')<span class="badge bg-warning text-dark">Chờ duyệt</span>
                        @elseif($payroll->status === 'approved')<span class="badge bg-primary">Đã duyệt</span>
                        @elseif($payroll->status === 'paid')<span class="badge bg-success">Đã trả</span>
                        @endif
                        @if($payroll->locked)
                            <span class="badge bg-danger"><i class="bi bi-lock"></i> Đã khoá</span>
                        @endif
                    </p>
                </div>
                <div class="mb-3">
                    <span class="text-muted small">Lương đi làm:</span>
                    <p class="fw-bold mb-0 text-success">+ {{ number_format($payroll->working_salary ?? 0, 0, '.', ',') }} VNĐ</p>
                </div>
                @if(($payroll->paid_leave_days ?? 0) > 0)
                <div class="mb-3">
                    <span class="text-muted small">Lương nghỉ phép:</span>
                    <p class="fw-bold mb-0 text-success">+ {{ number_format($payroll->paid_leave_salary, 0, '.', ',') }} VNĐ</p>
                </div>
                @endif
                @if(($payroll->overtime_salary ?? 0) > 0)
                <div class="mb-3">
                    <span class="text-muted small">Tăng ca:</span>
                    <p class="fw-bold mb-0 text-success">+ {{ number_format($payroll->overtime_salary ?? 0, 0, '.', ',') }} VNĐ</p>
                </div>
                @endif
                <div class="mb-3">
                    <span class="text-muted small">Phụ cấp:</span>
                    <p class="fw-bold mb-0 text-success">+ {{ number_format($payroll->allowance ?? 0, 0, '.', ',') }} VNĐ</p>
                </div>
                <div class="mb-3">
                    <span class="text-muted small">Thưởng:</span>
                    <p class="fw-bold mb-0 text-success">+ {{ number_format($payroll->bonus ?? 0, 0, '.', ',') }} VNĐ</p>
                </div>
                <div class="mb-3">
                    <span class="text-muted small">BHXH:</span>
                    <p class="fw-bold mb-0 text-danger">- {{ number_format($payroll->insurance ?? 0, 0, '.', ',') }} VNĐ</p>
                </div>
                <div class="mb-3">
                    <span class="text-muted small">Thuế:</span>
                    <p class="fw-bold mb-0 text-danger">- {{ number_format($payroll->tax ?? 0, 0, '.', ',') }} VNĐ</p>
                </div>
                <div class="mb-3">
                    <span class="text-muted small">Khấu trừ:</span>
                    <p class="fw-bold mb-0 text-danger">- {{ number_format($payroll->deduction ?? 0, 0, '.', ',') }} VNĐ</p>
                </div>
                @if(($payroll->late_penalty_fee ?? 0) > 0)
                <div class="mb-3">
                    <span class="text-muted small">Phạt đi muộn:</span>
                    <p class="fw-bold mb-0 text-danger">- {{ number_format($payroll->late_penalty_fee ?? 0, 0, '.', ',') }} VNĐ</p>
                </div>
                @endif
                <div class="mb-3 border-top pt-3">
                    <span class="text-muted small">Tổng lương:</span>
                    <p class="fw-bold text-primary" style="font-size:1.3rem;">{{ number_format($payroll->total_salary ?? 0, 0, '.', ',') }} VNĐ</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="confirmModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="confirmModalBody" class="mb-0"></p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn" data-bs-dismiss="modal">Hủy</button>
                <form id="confirmForm" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn" id="confirmBtn">Xác nhận</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function showModal(title, body, url, btnClass, btnText) {
    document.getElementById('confirmModalLabel').innerHTML = title;
    document.getElementById('confirmModalBody').innerHTML = body;
    document.getElementById('confirmForm').action = url;
    var btn = document.getElementById('confirmBtn');
    btn.className = 'btn ' + btnClass;
    btn.innerHTML = btnText;
    var modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    modal.show();
}

function confirmSendEmail() {
    showModal(
        '<i class="bi bi-envelope text-primary me-2"></i>Gửi email bảng lương',
        'Gửi phiếu lương qua email cho nhân viên?',
        '{{ route("accountant.payroll.send_email", $payroll) }}',
        'btn-primary',
        '<i class="bi bi-send me-1"></i> Gửi email'
    );
}

function confirmRecalculate() {
    showModal(
        '<i class="bi bi-arrow-clockwise text-primary me-2"></i>Tính lại lương',
        'Bạn có chắc chắn muốn tính lại bảng lương này?',
        '{{ route("accountant.payroll.recalculate", $payroll) }}',
        'btn-primary',
        '<i class="bi bi-arrow-clockwise me-1"></i> Tính lại'
    );
}

function confirmLock() {
    showModal(
        '<i class="bi bi-lock text-danger me-2"></i>Khoá bảng lương',
        'Sau khi khoá sẽ không thể chỉnh sửa. Bạn có chắc?',
        '{{ route("accountant.payroll.lock", $payroll) }}',
        'btn-danger',
        '<i class="bi bi-lock me-1"></i> Khoá'
    );
}

function confirmUnlock() {
    showModal(
        '<i class="bi bi-unlock text-success me-2"></i>Mở khoá bảng lương',
        'Bạn có chắc chắn muốn mở khoá bảng lương này?',
        '{{ route("accountant.payroll.unlock", $payroll) }}',
        'btn-success',
        '<i class="bi bi-unlock me-1"></i> Mở khoá'
    );
}
</script>
@endpush
