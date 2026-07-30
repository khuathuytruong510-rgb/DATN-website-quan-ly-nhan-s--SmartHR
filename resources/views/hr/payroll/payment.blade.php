@extends('layouts.app')

@section('title', 'Thanh toán lương')

@section('content')
@php
    $amountText = number_format($payroll->total_salary ?? 0, 0, '.', ',');
    $period = sprintf('%02d/%s', (int) $payroll->month, $payroll->year);
    $empName = optional($employee)->name ?? 'N/A';
    $noteTransfer = sprintf(
        'Thanh toán lương tháng %s cho nhân viên %s bằng chuyển khoản. Số tiền: %s ₫. STK: %s — %s (%s).',
        $period,
        $empName,
        $amountText,
        optional($employee)->account_number ?: 'chưa có',
        optional($employee)->account_holder ?: $empName,
        optional($employee)->bank_name ?: 'chưa rõ NH'
    );
    $noteCash = sprintf(
        'Thanh toán lương tháng %s cho nhân viên %s bằng tiền mặt. Số tiền: %s ₫.',
        $period,
        $empName,
        $amountText
    );
    $methodOld = old('payment_method', 'bank_transfer');
@endphp
<div class="content" style="max-width:980px;">
    <div class="page-head">
        <div>
            <h1><i class="bi bi-wallet2 me-2"></i>Thanh toán lương</h1>
            <p class="muted">{{ optional($employee)->name }} · Tháng {{ $payroll->month }}/{{ $payroll->year }}</p>
        </div>
        <div class="actions">
            <a href="{{ route('payroll.show', $payroll) }}" class="btn"><i class="bi bi-arrow-left me-1"></i> Chi tiết</a>
            <a href="{{ route('payroll.index') }}" class="btn">Danh sách</a>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul style="margin:0;padding-left:18px;">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid two-cols">
        <div class="card">
            <h3 style="margin-top:0;"><i class="bi bi-person me-2"></i>Thông tin nhân viên</h3>
            <p><strong>{{ optional($employee)->name }}</strong></p>
            <p class="muted">{{ optional($employee)->email }}</p>
            <p class="muted">{{ optional($employee)->position }} · {{ optional(optional($employee)->department)->name }}</p>

            <hr>
            <h3><i class="bi bi-cash-stack me-2"></i>Bảng lương</h3>
            <p>Kỳ: <strong>{{ sprintf('%02d', (int)$payroll->month) }}/{{ $payroll->year }}</strong></p>
            <p>Thực nhận: <strong style="color:var(--primary);font-size:22px;">{{ number_format($payroll->total_salary ?? 0, 0, '.', ',') }} ₫</strong></p>
            <p>Trạng thái: <span class="badge bg-primary">{{ $workflow->statusLabel($payroll->status) }}</span></p>
        </div>

        <div class="card">
            <h3 style="margin-top:0;"><i class="bi bi-qr-code me-2"></i>Thông tin nhận lương (QR/STK)</h3>
            <form method="POST" action="{{ route('payroll.payment.bank', $payroll) }}" enctype="multipart/form-data">
                @csrf
                <div class="field">
                    <label>Ngân hàng <span style="color:#dc2626;">*</span></label>
                    @include('components.bank-select', [
                        'name' => 'bank_name',
                        'value' => optional($employee)->bank_name,
                        'required' => true,
                    ])
                </div>
                <div class="field">
                    <label>Số tài khoản <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="account_number" value="{{ old('account_number', optional($employee)->account_number) }}" required maxlength="50" pattern="[0-9]{6,20}" title="Chỉ nhập số, 6–20 ký tự" placeholder="Chỉ nhập số">
                </div>
                <div class="field">
                    <label>Chủ tài khoản <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="account_holder" value="{{ old('account_holder', optional($employee)->account_holder ?: optional($employee)->name) }}" required maxlength="150">
                </div>
                <div class="field">
                    <label>Ảnh QR (tùy chọn)</label>
                    <input type="file" name="qr_image" accept="image/png,image/jpeg,image/jpg,image/webp">
                </div>
                @if(optional($employee)->qr_image)
                    <div style="margin-bottom:12px;">
                        <img src="{{ asset('storage/'.$employee->qr_image) }}" alt="QR" style="max-width:180px;border:1px solid var(--line);border-radius:8px;">
                    </div>
                @endif
                @if($payroll->status === 'ready_for_payment')
                    <button class="btn primary" type="submit"><i class="bi bi-save me-1"></i> Lưu thông tin nhận lương</button>
                @endif
            </form>
        </div>
    </div>

    @if($payroll->status === 'ready_for_payment')
        <div class="card" style="margin-top:16px;">
            <h3 style="margin-top:0;"><i class="bi bi-check-circle me-2"></i>Xác nhận thanh toán</h3>
            <form method="POST" action="{{ route('payroll.payment.confirm', $payroll) }}" id="payForm">
                @csrf
                <div class="grid two-cols">
                    <div class="field">
                        <label>Phương thức thanh toán <span style="color:#dc2626;">*</span></label>
                        <select name="payment_method" id="payment_method" required class="form-select">
                            <option value="bank_transfer" @selected($methodOld === 'bank_transfer')>Chuyển khoản</option>
                            <option value="cash" @selected($methodOld === 'cash')>Tiền mặt</option>
                        </select>
                    </div>
                    <div class="field" id="txnField">
                        <label id="txnLabel">Mã giao dịch <span class="txn-required" style="color:#dc2626;">*</span></label>
                        <input
                            type="text"
                            name="transaction_code"
                            id="transaction_code"
                            value="{{ old('transaction_code') }}"
                            maxlength="50"
                            data-pattern="[A-Za-z0-9\\-_]{6,50}"
                            placeholder="VD: FT240721123456"
                        >
                        <small id="txnHelp" class="text-muted">Bắt buộc khi chuyển khoản (6–50 ký tự chữ/số).</small>
                    </div>
                </div>
                <div class="field">
                    <label>Ghi chú (tự động)</label>
                    <div
                        id="noteBox"
                        data-note-transfer="{{ e($noteTransfer) }}"
                        data-note-cash="{{ e($noteCash) }}"
                        class="p-3 border rounded bg-light"
                    >{{ $methodOld === 'cash' ? $noteCash : $noteTransfer }}</div>
                </div>
                <button class="btn btn-success btn-lg" type="button" id="paySubmitBtn" onclick="confirmPayment()">
                    <i class="bi bi-wallet2 me-1"></i> Xác nhận đã thanh toán
                </button>
            </form>
        </div>
    @elseif($payroll->status === 'paid')
        <div class="card" style="margin-top:16px;">
            <div class="alert alert-success mb-0">
                <i class="bi bi-check-circle me-2"></i>Đã thanh toán{{ $payroll->paid_at ? ' lúc '.$payroll->paid_at->format('d/m/Y H:i') : '' }}.
            </div>
        </div>
    @endif
</div>

<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="paymentModalLabel">
                    <i class="bi bi-wallet2 text-success me-2"></i>Xác nhận thanh toán lương
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <div>
                        Thao tác này không thể hoàn tác. Vui lòng kiểm tra kỹ thông tin trước khi xác nhận.
                    </div>
                </div>
                <p class="mb-1">Bạn có chắc chắn muốn xác nhận đã thanh toán lương cho <strong>{{ $empName }}</strong>?</p>
                <p class="text-muted mb-0" style="font-size:13px;">Kỳ lương: <strong>{{ $period }}</strong> · Số tiền: <strong class="text-success">{{ $amountText }} ₫</strong></p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-success" onclick="document.getElementById('payForm').submit();">
                    <i class="bi bi-check-lg me-1"></i> Xác nhận thanh toán
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.btn-pay, a.btn-pay { background: #bbf7d0 !important; color: #166534 !important; border: 1px solid #86efac !important; font-weight: 700; }
.btn-pay:hover, a.btn-pay:hover { background: #86efac !important; color: #14532d !important; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var methodEl = document.getElementById('payment_method');
    var txnField = document.getElementById('txnField');
    var txnInput = document.getElementById('transaction_code');
    var txnHelp = document.getElementById('txnHelp');
    var noteBox = document.getElementById('noteBox');
    if (!methodEl || !txnInput || !noteBox) return;

    var noteTransfer = noteBox.getAttribute('data-note-transfer') || '';
    var noteCash = noteBox.getAttribute('data-note-cash') || '';
    var pattern = txnInput.getAttribute('data-pattern');

    function syncPaymentMethod() {
        var isCash = methodEl.value === 'cash';
        if (isCash) {
            txnField.style.display = 'none';
            txnInput.value = '';
            txnInput.removeAttribute('required');
            txnInput.removeAttribute('pattern');
            txnInput.disabled = true;
            noteBox.textContent = noteCash;
            if (txnHelp) txnHelp.style.display = 'none';
        } else {
            txnField.style.display = '';
            txnInput.disabled = false;
            txnInput.setAttribute('required', 'required');
            if (pattern) txnInput.setAttribute('pattern', pattern);
            noteBox.textContent = noteTransfer;
            if (txnHelp) txnHelp.style.display = '';
        }
    }

    methodEl.addEventListener('change', syncPaymentMethod);
    methodEl.addEventListener('input', syncPaymentMethod);
    syncPaymentMethod();
});

function confirmPayment() {
    var modal = new bootstrap.Modal(document.getElementById('paymentModal'));
    modal.show();
}
</script>
@endpush
