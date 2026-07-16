@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">
                <i class="bi bi-pencil-square"></i> Chỉnh sửa phiếu thanh toán
            </h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('salary_payments.update', $salaryPayment) }}">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><strong>Nhân viên</strong></label>
                            <p class="form-control-plaintext">{{ $salaryPayment->employee->name ?? '-' }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><strong>Mã phiếu</strong></label>
                            <p class="form-control-plaintext">{{ $salaryPayment->code }}</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><strong>Tháng/Năm</strong></label>
                            <p class="form-control-plaintext">{{ $salaryPayment->month }}/{{ $salaryPayment->year }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><strong>Trạng thái</strong></label>
                            <p class="form-control-plaintext">
                                <span class="badge text-bg-{{ $salaryPayment->status === 'paid' ? 'success' : 'warning' }}">
                                    {{ $salaryPayment->status === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán' }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <hr>

                <h5 class="mb-3"><strong>Thông tin thanh toán</strong></h5>

                <div class="mb-3">
                    <label for="payment_method" class="form-label"><strong>Hình thức thanh toán <span class="text-danger">*</span></strong></label>
                    <select name="payment_method" id="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                        <option value="">-- Chọn hình thức --</option>
                        <option value="bank_transfer" {{ old('payment_method', $salaryPayment->payment_method) === 'bank_transfer' ? 'selected' : '' }}>Chuyển khoản ngân hàng</option>
                        <option value="cash" {{ old('payment_method', $salaryPayment->payment_method) === 'cash' ? 'selected' : '' }}>Tiền mặt</option>
                    </select>
                    @error('payment_method')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div id="bank-info" class="d-none">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="bank" class="form-label"><strong>Ngân hàng</strong></label>
                                <select name="bank" id="bank" class="form-select @error('bank') is-invalid @enderror">
                                    <option value="">-- Chọn ngân hàng --</option>
                                    @foreach($banks as $bank)
                                        <option value="{{ $bank }}" {{ old('bank', $salaryPayment->bank) === $bank ? 'selected' : '' }}>{{ $bank }}</option>
                                    @endforeach
                                </select>
                                @error('bank')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="account_holder" class="form-label"><strong>Chủ tài khoản</strong></label>
                                <input type="text" name="account_holder" id="account_holder" class="form-control @error('account_holder') is-invalid @enderror" 
                                    value="{{ old('account_holder', $salaryPayment->account_holder) }}" placeholder="Tên chủ tài khoản">
                                @error('account_holder')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="account_number" class="form-label"><strong>Số tài khoản</strong></label>
                                <input type="text" name="account_number" id="account_number" class="form-control @error('account_number') is-invalid @enderror" 
                                    value="{{ old('account_number', $salaryPayment->account_number) }}" placeholder="Số tài khoản">
                                @error('account_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="transaction_code" class="form-label"><strong>Mã giao dịch</strong></label>
                                <input type="text" name="transaction_code" id="transaction_code" class="form-control @error('transaction_code') is-invalid @enderror" 
                                    value="{{ old('transaction_code', $salaryPayment->transaction_code) }}" placeholder="Mã giao dịch (nếu có)">
                                @error('transaction_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div id="cash-info" class="d-none">
                    <div class="mb-3">
                        <label for="cash_payer" class="form-label"><strong>Người thanh toán tiền mặt</strong></label>
                        <input type="text" name="cash_payer" id="cash_payer" class="form-control @error('cash_payer') is-invalid @enderror" 
                            value="{{ old('cash_payer', $salaryPayment->cash_payer) }}" placeholder="Tên người thanh toán">
                        @error('cash_payer')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label"><strong>Ghi chú</strong></label>
                    <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="Ghi chú thêm (nếu cần)">{{ old('notes', $salaryPayment->notes) }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <hr>

                <h5 class="mb-3"><strong>Thông tin lương</strong></h5>

                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label"><strong>Tổng lương</strong></label>
                            <p class="form-control-plaintext">{{ number_format($salaryPayment->total, 2) }} VNĐ</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label"><strong>Khấu trừ</strong></label>
                            <p class="form-control-plaintext">{{ number_format($salaryPayment->deductions, 2) }} VNĐ</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label"><strong>Thực lĩnh</strong></label>
                            <p class="form-control-plaintext text-success fw-bold fs-5">{{ number_format($salaryPayment->net, 2) }} VNĐ</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Cập nhật
                    </button>
                    <a href="{{ route('salary_payments.show', $salaryPayment) }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Quay lại
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethodSelect = document.getElementById('payment_method');
    const bankInfo = document.getElementById('bank-info');
    const cashInfo = document.getElementById('cash-info');

    function updatePaymentMethod() {
        if (paymentMethodSelect.value === 'bank_transfer') {
            bankInfo.classList.remove('d-none');
            cashInfo.classList.add('d-none');
        } else if (paymentMethodSelect.value === 'cash') {
            bankInfo.classList.add('d-none');
            cashInfo.classList.remove('d-none');
        } else {
            bankInfo.classList.add('d-none');
            cashInfo.classList.add('d-none');
        }
    }

    paymentMethodSelect.addEventListener('change', updatePaymentMethod);
    updatePaymentMethod(); // Initial call
});
</script>
@endsection
