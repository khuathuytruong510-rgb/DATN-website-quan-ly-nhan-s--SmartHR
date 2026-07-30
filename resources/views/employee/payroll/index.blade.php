@extends('layouts.employee')

@section('title', 'Bảng lương của tôi')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('me.dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item active">Bảng lương</li>
@endsection

@section('content')
<div style="max-width:900px;">
    <div class="mb-4">
        <h1 class="h2 fw-bold"><i class="bi bi-cash-stack me-2"></i>Bảng lương của tôi</h1>
        <p class="text-muted">Xem, xác nhận phiếu lương và lịch sử thanh toán</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title fw-bold"><i class="bi bi-bank me-2"></i>Yêu cầu thay đổi thông tin nhận lương</h5>
            <p class="text-muted small mb-3">Bạn không thể sửa trực tiếp STK/QR. Gửi yêu cầu để HR duyệt.</p>

            @php $emp = optional($payrolls->first())->employee; @endphp
            @if($emp)
                <div class="text-muted small mb-3">
                    Hiện tại:
                    <strong>{{ $emp->bank_name ?: '—' }}</strong> ·
                    <strong>{{ $emp->account_number ?: '—' }}</strong> ·
                    <strong>{{ $emp->account_holder ?: '—' }}</strong>
                </div>
            @endif

            <form method="POST" action="{{ route('me.payroll.bank_change') }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Ngân hàng</label>
                        @include('components.bank-select', [
                            'name' => 'bank_name',
                            'value' => '',
                            'required' => false,
                            'class' => 'form-select',
                        ])
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Số tài khoản</label>
                        <input type="text" name="account_number" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Chủ tài khoản</label>
                        <input type="text" name="account_holder" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Ảnh QR</label>
                        <input type="file" name="qr_image" accept="image/*" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Lý do</label>
                        <textarea name="note" rows="2" class="form-control"></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i> Gửi yêu cầu
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($payrolls->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-inbox" style="font-size:48px;color:#cbd5e1;"></i>
                <p class="mt-2">Chưa có phiếu lương.</p>
            </div>
        </div>
    @else
        <div class="d-flex flex-column gap-3">
            @foreach($payrolls as $p)
                <article class="card">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                            <div>
                                <h5 class="fw-bold mb-1">Tháng {{ $p->display_month }}</h5>
                                <span class="text-muted small">{{ optional($p->employee)->position }}</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                @if($p->status === 'pending')
                                    <span class="badge bg-secondary">Chờ duyệt</span>
                                @elseif($p->confirmation_status === 'issue_reported')
                                    <span class="badge bg-warning text-dark">Đã báo sự cố</span>
                                @elseif(in_array($p->status, ['waiting_confirmation', 'approved'], true))
                                    <span class="badge bg-warning text-dark">Chờ bạn xác nhận</span>
                                @elseif($p->status === 'ready_for_payment')
                                    <span class="badge bg-info">Đủ điều kiện thanh toán</span>
                                @elseif($p->status === 'paid')
                                    <span class="badge bg-success">Đã thanh toán</span>
                                @endif
                            </div>
                        </div>

                        <div class="row g-3 mb-3 p-3 bg-light rounded-3">
                            <div class="col-6 col-md-3">
                                <div class="text-muted small mb-1">Ngày công</div>
                                <div class="fw-bold">{{ $p->working_days ?? 0 }}</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-muted small mb-1">Phụ cấp</div>
                                <div class="fw-bold text-success">+{{ number_format($p->allowance ?? 0, 0, '.', ',') }}</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-muted small mb-1">Khấu trừ</div>
                                <div class="fw-bold text-danger">-{{ number_format(($p->insurance ?? 0)+($p->tax ?? 0)+($p->deduction ?? 0), 0, '.', ',') }}</div>
                            </div>
                            @if(($p->late_penalty_fee ?? 0) > 0)
                            <div class="col-6 col-md-3">
                                <div class="text-muted small mb-1">Phạt đi muộn</div>
                                <div class="fw-bold text-danger">-{{ number_format($p->late_penalty_fee ?? 0, 0, '.', ',') }} ₫</div>
                            </div>
                            @endif
                            <div class="col-12">
                                <div class="text-muted small mb-1">Thực lĩnh</div>
                                <div class="fw-bold text-primary" style="font-size:1.4rem;">{{ number_format($p->total_salary ?? 0, 0, '.', ',') }} ₫</div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            @if($p->confirmation_status === 'issue_reported')
                                <div class="alert alert-warning w-100 mb-0">
                                    <i class="bi bi-exclamation-triangle me-2"></i>Đã gửi báo cáo sự cố. Đang chờ phòng nhân sự / kế toán khắc phục.
                                </div>
                            @elseif(in_array($p->status, ['waiting_confirmation', 'approved'], true) && $p->confirmation_status !== 'confirmed')
                                <div class="alert alert-info w-100 mb-2">
                                    <i class="bi bi-info-circle me-2"></i>Phiếu lương cần bạn xác nhận{{ $p->sent_at ? ' (đã cập nhật '.optional($p->sent_at)->format('d/m/Y H:i').')' : '' }}.
                                </div>
                                <button class="btn btn-primary" type="button" onclick="confirmSalary({{ $p->id }})">
                                    <i class="bi bi-check2-square me-1"></i> Xác nhận bảng lương
                                </button>
                                <button class="btn btn-warning" type="button" onclick="openReportIssueModal({{ $p->id }})">
                                    <i class="bi bi-exclamation-triangle me-1"></i> Báo cáo sai sót
                                </button>
                            @elseif($p->status === 'paid')
                                <div class="alert alert-success w-100 mb-0">
                                    <i class="bi bi-check-circle me-2"></i>Đã thanh toán{{ $p->paid_at ? ' lúc '.$p->paid_at->format('d/m/Y H:i') : '' }}
                                    @if($p->payment_method) · {{ $p->payment_method }} @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        @php $paidList = $payrolls->where('status', 'paid'); @endphp
        @if($paidList->isNotEmpty())
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title fw-bold mb-3"><i class="bi bi-clock-history me-2"></i>Lịch sử thanh toán</h5>
                    <div class="d-flex flex-column gap-2">
                        @foreach($paidList as $paid)
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                                <div>
                                    <strong>Tháng {{ $paid->display_month }}</strong>
                                    <div class="text-muted small">{{ optional($paid->paid_at)->format('d/m/Y H:i') ?? '—' }} · {{ $paid->payment_method ?? '—' }}</div>
                                </div>
                                <div class="fw-bold text-primary">{{ number_format($paid->total_salary ?? 0, 0, '.', ',') }} ₫</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>

<div class="modal fade" id="confirmSalaryModal" tabindex="-1" aria-labelledby="confirmSalaryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="confirmSalaryModalLabel">
                    <i class="bi bi-check2-square text-primary me-2"></i>Xác nhận bảng lương
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn <strong class="text-primary">xác nhận</strong> bảng lương này là chính xác?</p>
                <p class="text-muted mb-0" style="font-size:13px;">Sau khi xác nhận, nếu phát hiện sai sót vui lòng báo cáo ngay cho bộ phận kế toán.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn" data-bs-dismiss="modal">Hủy</button>
                <form id="confirmSalaryForm" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-square me-1"></i> Xác nhận
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reportIssueModal" tabindex="-1" aria-labelledby="reportIssueModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="reportIssueModalLabel">
                    <i class="bi bi-exclamation-triangle text-warning me-2"></i>Báo cáo sai sót lương
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="reportIssueForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-muted mb-3">Mô tả chi tiết sai sót bạn phát hiện trên phiếu lương:</p>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Nội dung báo cáo <span class="text-danger">*</span></label>
                        <textarea name="issue_report" id="issue_report_text" rows="4" required class="form-control" placeholder="Ví dụ: Ngày công không đúng, thiếu phụ cấp..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-send me-1"></i> Gửi báo cáo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmSalary(payrollId) {
    document.getElementById('confirmSalaryForm').action = '/me/payroll/' + payrollId + '/confirm';
    var modal = new bootstrap.Modal(document.getElementById('confirmSalaryModal'));
    modal.show();
}

function openReportIssueModal(payrollId) {
    document.getElementById('reportIssueForm').action = '/me/payroll/' + payrollId + '/report-issue';
    document.getElementById('issue_report_text').value = '';
    var modal = new bootstrap.Modal(document.getElementById('reportIssueModal'));
    modal.show();
}
</script>
@endpush
