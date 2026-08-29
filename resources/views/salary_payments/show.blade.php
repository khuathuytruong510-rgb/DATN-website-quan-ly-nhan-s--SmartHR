@extends('layouts.app')

@section('content')
<div class="container">
    {{-- Header --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">
                <i class="bi bi-receipt"></i> Phiếu thanh toán lương #{{ $salaryPayment->code }}
            </h3>
        </div>
    </div>

    <div class="row">
        {{-- Main Info --}}
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Thông tin nhân viên</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nhân viên:</strong> {{ $salaryPayment->employee->name ?? '-' }}</p>
                            <p><strong>Phòng ban:</strong> {{ $salaryPayment->employee->department->name ?? '-' }}</p>
                            <p><strong>Chức vụ:</strong> {{ $salaryPayment->employee->position ?? '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Mã phiếu:</strong> <span class="badge text-bg-info">{{ $salaryPayment->code }}</span></p>
                            <p><strong>Tháng/Năm:</strong> {{ sprintf('%02d/%04d', $salaryPayment->month, $salaryPayment->year) }}</p>
                            <p><strong>Trạng thái:</strong> 
                                <span class="badge text-bg-{{ $salaryPayment->status === 'paid' ? 'success' : ($salaryPayment->status === 'pending' ? 'warning' : 'secondary') }}">
                                    {{ $salaryPayment->status === 'paid' ? 'Đã thanh toán' : ($salaryPayment->status === 'pending' ? 'Chưa thanh toán' : 'Hủy') }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Chi tiết lương</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Tổng lương</strong></td>
                                <td class="text-end">{{ number_format($salaryPayment->total, 2) }} VNĐ</td>
                            </tr>
                            <tr class="table-danger">
                                <td><strong>Khấu trừ (BH + Thuế)</strong></td>
                                <td class="text-end">{{ number_format($salaryPayment->deductions, 2) }} VNĐ</td>
                            </tr>
                            <tr class="table-success">
                                <td><strong>Thực lĩnh</strong></td>
                                <td class="text-end"><strong class="fs-5">{{ number_format($salaryPayment->net, 2) }} VNĐ</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Payment Info --}}
            @if($salaryPayment->payment_method)
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-credit-card"></i> 
                            Thông tin thanh toán
                        </h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Hình thức:</strong> 
                            <span class="badge text-bg-info">
                                {{ $salaryPayment->payment_method === 'bank_transfer' ? 'Chuyển khoản' : 'Tiền mặt' }}
                            </span>
                        </p>

                        @if($salaryPayment->payment_method === 'bank_transfer')
                            <hr>
                            <p><strong>Ngân hàng:</strong> {{ $salaryPayment->bank ?? '-' }}</p>
                            <p><strong>Chủ tài khoản:</strong> {{ $salaryPayment->account_holder ?? '-' }}</p>
                            <p><strong>Số tài khoản:</strong> {{ $salaryPayment->account_number ?? '-' }}</p>
                            @if($salaryPayment->transaction_code)
                                <p><strong>Mã giao dịch:</strong> <code>{{ $salaryPayment->transaction_code }}</code></p>
                            @endif
                        @elseif($salaryPayment->payment_method === 'cash')
                            <hr>
                            <p><strong>Người thanh toán:</strong> {{ $salaryPayment->cash_payer ?? '-' }}</p>
                        @endif

                        @if($salaryPayment->notes)
                            <hr>
                            <p><strong>Ghi chú:</strong> {{ $salaryPayment->notes }}</p>
                        @endif

                        @if($salaryPayment->paid_at)
                            <hr>
                            <p><strong>Ngày thanh toán:</strong> {{ $salaryPayment->paid_at->format('d/m/Y H:i') }}</p>
                            <p><strong>Người thanh toán:</strong> {{ $salaryPayment->paidBy->name ?? '-' }}</p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Action Buttons --}}
            <div class="mb-4">
                @if($salaryPayment->status === 'pending' && auth()->user()?->is_accountant)
                    <a href="{{ route('salary_payments.edit', $salaryPayment) }}" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Chỉnh sửa
                    </a>
                    <form method="POST" action="{{ route('salary_payments.action', $salaryPayment) }}" style="display:inline;">
                        @csrf
                        <input type="hidden" name="payment_method" value="{{ old('payment_method', $salaryPayment->payment_method) }}">
                        <input type="hidden" name="bank" value="{{ old('bank', $salaryPayment->bank) }}">
                        <input type="hidden" name="account_holder" value="{{ old('account_holder', $salaryPayment->account_holder) }}">
                        <input type="hidden" name="account_number" value="{{ old('account_number', $salaryPayment->account_number) }}">
                        <input type="hidden" name="transaction_code" value="{{ old('transaction_code', $salaryPayment->transaction_code) }}">
                        <input type="hidden" name="cash_payer" value="{{ old('cash_payer', $salaryPayment->cash_payer) }}">
                        <button type="submit" class="btn btn-success" onclick="return confirm('Xác nhận thanh toán lương?')">
                            <i class="bi bi-check-circle"></i> Thanh toán
                        </button>
                    </form>
                    <form method="POST" action="{{ route('salary_payments.destroy', $salaryPayment) }}" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Xác nhận xóa phiếu thanh toán?')">
                            <i class="bi bi-trash"></i> Xóa
                        </button>
                    </form>
                @elseif($salaryPayment->status === 'paid')
                    <form method="POST" action="{{ route('salary_payments.send_email', $salaryPayment) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-info" onclick="return confirm('Gửi phiếu lương đến email nhân viên?')">
                            <i class="bi bi-envelope"></i> Gửi phiếu qua email
                        </button>
                    </form>
                    <span class="badge text-bg-success" style="padding: 10px; font-size: 1rem;">
                        <i class="bi bi-check-circle"></i> Đã thanh toán thành công
                    </span>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-md-4">
            {{-- Summary Card --}}
            <div class="card shadow-sm mb-4 border-success">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Thực lĩnh</h6>
                    <h2 class="text-success mb-0">{{ number_format($salaryPayment->net, 0) }}</h2>
                    <small class="text-muted">VNĐ</small>
                </div>
            </div>

            {{-- Payroll Link --}}
            @if($salaryPayment->payroll)
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Bảng lương liên quan</h6>
                    </div>
                    <div class="card-body">
                        <a href="{{ route('payroll.show', $salaryPayment->payroll) }}" class="btn btn-sm btn-primary w-100">
                            <i class="bi bi-file-earmark"></i> Xem bảng lương
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Logs --}}
    @if($salaryPayment->logs && count($salaryPayment->logs) > 0)
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Lịch sử hoạt động</h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    @foreach($salaryPayment->logs->reverse() as $log)
                        <div class="timeline-item mb-3">
                            <div class="timeline-marker">
                                <i class="bi bi-circle-fill text-primary"></i>
                            </div>
                            <div class="timeline-content ms-3">
                                <p class="mb-1">
                                    <strong>{{ $log->action }}</strong>
                                    <small class="text-muted">{{ $log->created_at->format('d/m/Y H:i:s') }}</small>
                                </p>
                                @if($log->user)
                                    <p class="mb-1 text-muted">Người thực hiện: <strong>{{ $log->user->name }}</strong></p>
                                @endif
                                @if($log->notes)
                                    <p class="mb-0 text-muted"><em>{{ $log->notes }}</em></p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>

<style>
.timeline {
    position: relative;
    padding-left: 0;
}

.timeline-item {
    display: flex;
    position: relative;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: 11px;
    top: 30px;
    height: calc(100% - 30px);
    width: 2px;
    background-color: #e9ecef;
}

.timeline-item:last-child::before {
    display: none;
}

.timeline-marker {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #fff;
    border: 2px solid #e9ecef;
    border-radius: 50%;
    position: relative;
    z-index: 1;
}
</style>
@endsection
