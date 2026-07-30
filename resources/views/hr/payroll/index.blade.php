@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h3 class="fw-bold mb-1">
                        <i class="bi bi-calculator me-2"></i>Bảng lương nhân viên
                    </h3>
                    <p class="text-muted mb-0">
                        Quản lý và tính lương nhân viên theo tháng.
                    </p>
                </div>

                <form method="POST" action="{{ route('payroll.generate') }}" id="generatePayrollForm">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-auto">
                            <label class="form-label fw-semibold">Tháng</label>
                            <select name="month" class="form-select">
                                @for($i=1;$i<=12;$i++)
                                    <option value="{{ $i }}" {{ $month==$i?'selected':'' }}>
                                        Tháng {{ $i }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-auto">
                            <label class="form-label fw-semibold">Năm</label>
                            <select name="year" class="form-select">
                                @for($y=2025;$y<=2035;$y++)
                                    <option value="{{ $y }}" {{ $year==$y?'selected':'' }}>
                                        {{ $y }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-primary" type="button" onclick="confirmGenerate()">
                                <i class="bi bi-calculator me-1"></i> Tính lương
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle payroll-table mb-0">
                    <thead>
                        <tr>
                            <th>Mã NV</th>
                            <th>Nhân viên</th>
                            <th>Chức vụ</th>
                            <th class="text-center">Tháng</th>
                            <th class="text-end">Lương CB</th>
                            <th class="text-center">Ngày công</th>
                            <th class="text-center">Nghỉ phép</th>
                            <th class="text-center">TC Ngày</th>
                            <th class="text-center">TC Giờ</th>
                            <th class="text-end">Lương đi làm</th>
                            <th class="text-end">Lương nghỉ phép</th>
                            <th class="text-end">TC Ngày</th>
                            <th class="text-end">TC Giờ</th>
                            <th class="text-end">Tổng TC</th>
                            <th class="text-end">Phụ cấp</th>
                            <th class="text-end">Thưởng</th>
                            <th class="text-end">BH</th>
                            <th class="text-end">Thuế</th>
                            <th class="text-end">Khấu trừ</th>
                            <th class="text-end">Phạt muộn</th>
                            <th class="text-end">Thực nhận</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payrolls as $payroll)
                        @php
                            $user = auth()->user();
                            $isHrOrAdmin = $user->is_admin || $user->is_hr;
                            $isAccountantOrAdmin = $user->is_admin || $user->is_accountant;

                            $paidLeave = $payroll->paid_leave_days ?? 0;
                            $unpaidLeave = $payroll->unpaid_leave_days ?? 0;
                            $hasLeave = $paidLeave > 0 || $unpaidLeave > 0;

                            $leaveSalary = $payroll->paid_leave_salary ?? 0;

                            // Trạng thái badge
                            $statusBadge = match($payroll->status) {
                                'pending' => '<span class="badge bg-secondary"><i class="bi bi-hourglass-split"></i> Chờ duyệt</span>',
                                'waiting_confirmation' => '<span class="badge bg-warning text-dark"><i class="bi bi-envelope"></i> Chờ xác nhận NV</span>',
                                'approved' => '<span class="badge bg-info text-dark"><i class="bi bi-check2-square"></i> Đã xác nhận</span>',
                                'ready_for_payment' => '<span class="badge bg-primary"><i class="bi bi-wallet2"></i> Sẵn sàng thanh toán</span>',
                                'paid' => '<span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Đã thanh toán</span>',
                                default => '<span class="badge bg-secondary">' . $payroll->status . '</span>',
                            };

                            // Hiển thị từ chối nếu có issue_reported
                            if ($payroll->confirmation_status === 'issue_reported') {
                                $statusBadge = '<span class="badge bg-danger"><i class="bi bi-exclamation-triangle"></i> Báo sai sót</span>';
                            }
                        @endphp
                        <tr>
                            <td><code>{{ $payroll->employee->employee_code ?? '—' }}</code></td>
                            <td>
                                <div class="fw-semibold">{{ $payroll->employee->name }}</div>
                            </td>
                            <td>
                                @switch($payroll->employee->position)
                                    @case('Giám Đốc')
                                        <span class="badge bg-dark">Giám đốc</span>
                                        @break
                                    @case('Trưởng Phòng Nhân Sự')
                                        <span class="badge bg-primary">Trưởng phòng</span>
                                        @break
                                    @default
                                        <span class="badge bg-light border text-dark">Nhân viên</span>
                                @endswitch
                            </td>
                            <td class="text-center">{{ sprintf('%02d', $payroll->month) }}/{{ $payroll->year }}</td>
                            <td class="text-end">{{ number_format($payroll->base_salary) }}</td>

                            {{-- Ngày công --}}
                            <td class="text-center">
                                @if($payroll->working_days >= $payroll->required_working_days)
                                    <span class="text-success fw-semibold">{{ $payroll->working_days }}/{{ $payroll->required_working_days }}</span>
                                @elseif($payroll->working_days > 0)
                                    <span class="text-warning fw-semibold">{{ $payroll->working_days }}/{{ $payroll->required_working_days }}</span>
                                @else
                                    <span class="text-danger fw-semibold">{{ $payroll->working_days }}/{{ $payroll->required_working_days }}</span>
                                @endif
                            </td>

                            {{-- Nghỉ phép --}}
                            <td class="text-center">
                                @if($hasLeave)
                                    <div class="d-flex flex-column gap-1" style="font-size:12px;">
                                        @if($paidLeave > 0)
                                            <span class="text-success fw-semibold">
                                                <i class="bi bi-check-circle"></i> {{ number_format($paidLeave, 1) }} / {{ number_format($leaveSalary) }}
                                            </span>
                                        @endif
                                        @if($unpaidLeave > 0)
                                            <span class="text-danger fw-semibold">
                                                {{ number_format($unpaidLeave, 1) }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            {{-- TC Ngày (số ngày tăng ca) --}}
                            <td class="text-center">
                                @if($payroll->overtime_days > 0)
                                    <span class="text-primary fw-semibold">+{{ $payroll->overtime_days }}</span>
                                @else - @endif
                            </td>

                            {{-- TC Giờ --}}
                            <td class="text-center">
                                @if($payroll->overtime_hours > 0)
                                    <span class="text-primary fw-semibold">{{ number_format($payroll->overtime_hours, 1) }}h</span>
                                @else - @endif
                            </td>

                            {{-- Lương đi làm --}}
                            <td class="text-end">{{ number_format($payroll->working_salary) }}</td>

                            {{-- Lương nghỉ phép --}}
                            <td class="text-end">
                                @if($leaveSalary > 0)
                                    <span class="text-success fw-semibold">{{ number_format($leaveSalary) }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>

                            {{-- TC Ngày (tiền) --}}
                            <td class="text-end">
                                @if($payroll->overtime_day_salary > 0)
                                    <span class="text-primary fw-semibold">{{ number_format($payroll->overtime_day_salary) }}</span>
                                @else - @endif
                            </td>

                            {{-- TC Giờ (tiền) --}}
                            <td class="text-end">
                                @if($payroll->overtime_hour_salary > 0)
                                    <span class="text-primary fw-semibold">{{ number_format($payroll->overtime_hour_salary) }}</span>
                                @else - @endif
                            </td>

                            {{-- Tổng TC --}}
                            <td class="text-end">
                                @if($payroll->overtime_salary > 0)
                                    <span class="text-primary fw-semibold">{{ number_format($payroll->overtime_salary) }}</span>
                                @else - @endif
                            </td>

                            <td class="text-end">{{ number_format($payroll->allowance) }}</td>

                            <td class="text-end">
                                @if($payroll->bonus > 0)
                                    <span class="text-success fw-semibold">{{ number_format($payroll->bonus) }}</span>
                                @else - @endif
                            </td>

                            <td class="text-end text-danger">{{ number_format($payroll->insurance) }}</td>

                            <td class="text-end">
                                @if($payroll->tax > 0)
                                    <span class="text-danger">{{ number_format($payroll->tax) }}</span>
                                @else 0 @endif
                            </td>

                            <td class="text-end">
                                @if(($payroll->deduction ?? 0) > 0)
                                    <span class="text-danger fw-semibold">{{ number_format($payroll->deduction) }}</span>
                                @else 0 @endif
                            </td>

                            <td class="text-end">
                                @if(($payroll->late_penalty_fee ?? 0) > 0)
                                    <span class="text-danger fw-semibold">-{{ number_format($payroll->late_penalty_fee) }}</span>
                                @else - @endif
                            </td>

                            {{-- Thực nhận --}}
                            <td class="text-end">
                                <span class="fw-bold text-success fs-5">{{ number_format($payroll->total_salary) }}đ</span>
                            </td>

                            {{-- Trạng thái --}}
                            <td class="text-center">{!! $statusBadge !!}</td>

                            {{-- Hành động --}}
                            <td class="text-center">
                                <div class="d-inline-flex align-items-center gap-2 flex-wrap justify-content-center">
                                    <a href="{{ route('payroll.show', $payroll) }}" class="btn btn-sm btn-outline-primary" title="Xem chi tiết">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    {{-- Nút Duyệt: chỉ HR/Admin, chỉ khi pending --}}
                                    @if($isHrOrAdmin && $payroll->status === 'pending')
                                        <button class="btn btn-sm btn-success" type="button"
                                            onclick="confirmApprove({{ $payroll->id }}, '{{ optional($payroll->employee)->name }}')"
                                            title="Duyệt">
                                            <i class="bi bi-check-lg"></i> Duyệt
                                        </button>
                                    @endif

                                    {{-- Báo sai sót --}}
                                    @if($payroll->confirmation_status === 'issue_reported')
                                        @if(auth()->user()->is_admin || auth()->user()->is_hr || auth()->user()->is_accountant)
                                            <a href="{{ route('payroll.issues.fix_form', $payroll) }}" class="btn btn-sm btn-warning">
                                                <i class="bi bi-tools"></i> Khắc phục
                                            </a>
                                        @endif
                                    @endif

                                    {{-- Nút Thanh toán: khi ready_for_payment (đã xác nhận) --}}
                                    @if($isAccountantOrAdmin && $payroll->status === 'ready_for_payment')
                                        <a href="{{ route('payroll.payment.show', $payroll) }}" class="btn btn-sm btn-success">
                                            <i class="bi bi-wallet2"></i> Thanh toán
                                        </a>
                                    @endif

                                    {{-- Đã thanh toán --}}
                                    @if($payroll->status === 'paid')
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Đã thanh toán</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="23" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox" style="font-size:48px;color:#cbd5e1;"></i>
                                <p class="mt-2">Chưa có dữ liệu bảng lương.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="approveModalLabel">
                    <i class="bi bi-check-circle text-success me-2"></i>Duyệt bảng lương
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Bạn có chắc chắn muốn <strong class="text-success">duyệt</strong> bảng lương của nhân viên <strong id="approveEmpName"></strong>?</p>
                <p class="text-muted mt-2 mb-0" style="font-size:13px;">Sau khi duyệt, phiếu lương sẽ được gửi cho nhân viên xác nhận.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn" data-bs-dismiss="modal">Hủy</button>
                <form id="approveForm" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg me-1"></i> Xác nhận duyệt
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="generateModal" tabindex="-1" aria-labelledby="generateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="generateModalLabel">
                    <i class="bi bi-calculator text-primary me-2"></i>Tính lương tháng
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn tính lương cho tất cả nhân viên?</p>
                <p class="text-muted mb-0" style="font-size:13px;">Hệ thống sẽ tự động tính toán lương dựa trên dữ liệu chấm công, đơn nghỉ phép, tăng ca đã duyệt.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('generatePayrollForm').submit();">
                    <i class="bi bi-calculator me-1"></i> Xác nhận tính lương
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.btn-pay-soft { background: #bbf7d0 !important; color: #166534 !important; border: 1px solid #86efac !important; font-weight: 700; }
.btn-pay-soft:hover { background: #86efac !important; color: #14532d !important; }
body { background: #f5f7fb; }
.card { border: none; border-radius: 14px; overflow: hidden; }
.card-body { padding: 1.25rem; }
.table { margin-bottom: 0; }
.payroll-table thead { background: #f8f9fa; }
.payroll-table thead th { font-size: 12px; font-weight: 700; color: #6c757d; text-transform: uppercase; letter-spacing: .5px; padding: 14px 10px; border-bottom: 2px solid #e9ecef; white-space: nowrap; }
.payroll-table tbody td { padding: 14px 10px; vertical-align: middle; white-space: nowrap; border-color: #f1f3f5; font-size: 13px; }
.payroll-table tbody tr { transition: all .18s ease; }
.payroll-table tbody tr:hover { background: #f8fbff; }
.badge { font-size: 12px; font-weight: 500; padding: 5px 10px; border-radius: 6px; }
.btn { border-radius: 10px; font-weight: 600; }
.form-control, .form-select { border-radius: 10px; min-height: 42px; }
.text-end { font-variant-numeric: tabular-nums; }
.text-success { font-weight: 600; }
.text-danger { font-weight: 600; }
.table-responsive { border-radius: 12px; }
h3 { color: #212529; }
.text-muted { font-size: 14px; }
</style>
@endpush

@push('scripts')
<script>
function confirmApprove(payrollId, empName) {
    document.getElementById('approveEmpName').textContent = empName;
    document.getElementById('approveForm').action = '/hr/payroll/' + payrollId + '/approve';
    var modal = new bootstrap.Modal(document.getElementById('approveModal'));
    modal.show();
}

function confirmGenerate() {
    var modal = new bootstrap.Modal(document.getElementById('generateModal'));
    modal.show();
}
</script>
@endpush
