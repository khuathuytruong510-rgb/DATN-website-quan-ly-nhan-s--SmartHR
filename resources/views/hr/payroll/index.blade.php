@extends('layouts.app')

@section('content')

<div class="container-fluid">

    {{-- Header --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h3 class="fw-bold mb-1">
                        {{ !empty($paymentFocus) ? 'Thanh toán lương' : 'Bảng lương nhân viên' }}
                    </h3>
                    <p class="text-muted mb-0">
                        @if(!empty($paymentFocus))
                            Chỉ phiếu nhân viên đã xác nhận. Kế toán thanh toán → salary_payment → Đã trả. Không thanh toán phiếu chưa xác nhận hoặc đã trả.
                        @else
                            HR chốt dữ liệu kỳ → Kế toán tính → HR kiểm tra → Giám đốc phê duyệt cuối → Nhân viên xác nhận → Kế toán thanh toán.
                        @endif
                    </p>
                </div>

                @php
                    $user = auth()->user();
                    $canGenerate = $user->is_accountant && empty($paymentFocus);
                    $pendingHrCount = $payrolls->whereIn('status', \App\Services\PayrollPaymentWorkflowService::calculatedStatuses())->count();
                    $pendingDirectorCount = $payrolls->whereIn('status', \App\Services\PayrollPaymentWorkflowService::hrCheckedStatuses())->count();
                    $canBulkHrReview = $user->is_hr;
                    $canBulkFinalApprove = $user->is_director;
                    $periodLocked = (bool) optional($periodLock ?? null)->is_locked;
                @endphp

                <div class="d-flex flex-column gap-2 align-items-stretch" style="min-width:min(520px,100%);">
                    <form method="GET" action="{{ route('payroll.index') }}">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label>Tháng</label>
                                <select name="month" class="form-select">
                                    @for($i=1;$i<=12;$i++)
                                        <option value="{{ $i }}" {{ $month==$i?'selected':'' }}>
                                            Tháng {{ $i }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Năm</label>
                                <select name="year" class="form-select">
                                    @for($y=2025;$y<=2035;$y++)
                                        <option value="{{ $y }}" {{ $year==$y?'selected':'' }}>
                                            {{ $y }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end gap-2 flex-wrap">
                                <button class="btn btn-outline-primary" type="submit">Xem kỳ lương</button>
                                @if($canGenerate)
                                <a class="btn btn-primary" href="{{ route('accountant.payroll.generate') }}">
                                    <i class="bi bi-calculator"></i>
                                    Sang tính lương
                                </a>
                                @endif
                                <a class="btn btn-outline-secondary"
                                   href="{{ route('payroll.print', ['month' => $month, 'year' => $year]) }}"
                                   target="_blank">
                                    <i class="bi bi-printer"></i>
                                    In bảng lương
                                </a>
                            </div>
                        </div>
                    </form>

                    @if($user->is_hr)
                        @if($periodLocked)
                            <form method="POST" action="{{ route('payroll.period.unlock') }}"
                                  onsubmit="return confirm('Mở khóa kỳ {{ sprintf('%02d/%d', $month, $year) }}? Sau đó có thể sửa chấm công/nghỉ phép. Phải chốt lại trước khi Kế toán tính.');">
                                @csrf
                                <input type="hidden" name="month" value="{{ $month }}">
                                <input type="hidden" name="year" value="{{ $year }}">
                                <div class="d-flex gap-2 flex-wrap align-items-end">
                                    <div style="flex:1;min-width:220px;">
                                        <label class="form-label mb-1">Kỳ {{ sprintf('%02d/%d', $month, $year) }} đang khóa</label>
                                        <input type="text" name="unlock_reason" class="form-control" required minlength="10" maxlength="500"
                                               placeholder="Lý do mở khóa (bắt buộc ≥ 10 ký tự, ghi nhật ký)">
                                    </div>
                                    <button type="submit" class="btn btn-outline-danger">Mở khóa kỳ</button>
                                </div>
                                <p class="text-muted mb-0 mt-1" style="font-size:12px;">
                                    Chốt lúc {{ optional($periodLock->locked_at)->format('d/m/Y H:i') }}
                                    @if($periodLock->locker)
                                        · {{ $periodLock->locker->name }}
                                    @endif
                                </p>
                            </form>
                        @else
                            <form method="POST" action="{{ route('payroll.period.lock') }}"
                                  onsubmit="return confirm('Chốt dữ liệu kỳ {{ sprintf('%02d/%d', $month, $year) }}? Sau khi chốt, không sửa chấm công/nghỉ phép của kỳ; Kế toán mới được tính lương.');">
                                @csrf
                                <input type="hidden" name="month" value="{{ $month }}">
                                <input type="hidden" name="year" value="{{ $year }}">
                                <button type="submit" class="btn btn-outline-primary">
                                    Chốt dữ liệu kỳ {{ sprintf('%02d/%d', $month, $year) }}
                                </button>
                                @if(optional($periodLock ?? null)->unlock_reason)
                                    <p class="text-muted mb-0 mt-1" style="font-size:12px;">
                                        Lần mở khóa trước: {{ $periodLock->unlock_reason }}
                                        @if($periodLock->unlocked_at)
                                            · {{ $periodLock->unlocked_at->format('d/m/Y H:i') }}
                                        @endif
                                    </p>
                                @endif
                            </form>
                        @endif
                    @elseif($canGenerate && ! $periodLocked)
                        <p class="text-muted mb-0" style="font-size:13px;">HR chưa chốt kỳ {{ sprintf('%02d/%d', $month, $year) }}. Kế toán chưa được tính lương.</p>
                    @endif

                    @if($canBulkHrReview)
                        <form method="POST" action="{{ route('payroll.review_all') }}"
                              onsubmit="return confirm('Xác nhận đã kiểm tra dữ liệu nhân sự trên {{ $pendingHrCount }} bảng lương tháng {{ sprintf('%02d/%d', $month, $year) }}? Sau bước này Giám đốc sẽ phê duyệt cuối.');">
                            @csrf
                            <input type="hidden" name="month" value="{{ $month }}">
                            <input type="hidden" name="year" value="{{ $year }}">
                            <button type="submit" class="btn btn-success"
                                    @disabled($pendingHrCount < 1)
                                    title="{{ $pendingHrCount < 1 ? 'Không có phiếu chờ HR kiểm tra' : 'HR kiểm tra dữ liệu tất cả phiếu kế toán đã tính' }}">
                                <i class="bi bi-check2-all"></i>
                                Kiểm tra bảng lương
                                @if($pendingHrCount > 0)
                                    ({{ $pendingHrCount }})
                                @endif
                            </button>
                        </form>
                    @endif

                    @if($canBulkFinalApprove)
                        <form method="POST" action="{{ route('payroll.approve_all') }}"
                              onsubmit="return confirm('Bạn đang phê duyệt {{ $pendingDirectorCount }} phiếu lương của tháng {{ sprintf('%02d/%d', $month, $year) }}. Sau khi phê duyệt, các phiếu sẽ chuyển sang chờ nhân viên xác nhận.\n\nBạn có chắc chắn tiếp tục?');">
                            @csrf
                            <input type="hidden" name="month" value="{{ $month }}">
                            <input type="hidden" name="year" value="{{ $year }}">
                            <button type="submit" class="btn btn-success"
                                    @disabled($pendingDirectorCount < 1)
                                    title="{{ $pendingDirectorCount < 1 ? 'Không có phiếu chờ phê duyệt cuối' : 'Giám đốc phê duyệt cuối các phiếu HR đã kiểm tra' }}">
                                <i class="bi bi-check2-all"></i>
                                Phê duyệt cuối
                                @if($pendingDirectorCount > 0)
                                    ({{ $pendingDirectorCount }})
                                @endif
                            </button>
                        </form>
                    @endif
                </div>

            </div>
        </div>
    </div>

    {{-- Bảng lương --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle payroll-table mb-0">
                    <thead>
                        <tr>
                            <th>Nhân viên</th>
                            <th>Chức vụ</th>
                            <th class="text-center">Tháng</th>
                            <th class="text-end">Lương CB</th>
                            <th class="text-center">Ngày công</th>
                            <th class="text-center">TC Ngày</th>
                            <th class="text-center">Nghỉ phép (Có/Không)</th>
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
                            <th class="text-end">Phạt đi muộn</th>
                            <th class="text-end">Thực nhận</th>
                            <th class="text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payrolls as $payroll)
                        @php
                            // Tính toán lương công thực tế & lương ngày nghỉ có lương
                            $dailySalary = $payroll->daily_salary ?? ($payroll->base_salary / ($payroll->required_working_days ?? 26));
                            
                            $actualDays = min($payroll->working_days, $payroll->required_working_days);
                            $actualWorkingSalary = $actualDays * $dailySalary;
                            
                            $paidLeaveDays = $payroll->paid_leave_days ?? 0;
                            $paidLeaveSalary = $paidLeaveDays * $dailySalary;
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">
                                    {{ $payroll->employee->name }}
                                </div>
                            </td>

                            <td>
                               @switch($payroll->employee->position)
                                    @case('Giám Đốc')
                                        <span class="badge text-bg-dark">Giám đốc</span>
                                        @break
                                    @case('Trưởng Phòng Nhân Sự')
                                        <span class="badge text-bg-primary">Trưởng phòng</span>
                                        @break
                                    @default
                                        <span class="badge text-bg-light border text-dark">Nhân viên</span>
                                @endswitch
                            </td>

                            <td class="text-center">
                                {{ sprintf('%02d', $payroll->month) }}/{{ $payroll->year }}
                            </td>

                            <td class="text-end">
                                {{ number_format($payroll->base_salary) }}
                            </td>

                            {{-- Cột Ngày công đi làm --}}
                            <td class="text-center">
                                @if(($payroll->working_days + $paidLeaveDays) >= $payroll->required_working_days)
                                    <span class="text-success fw-semibold">
                                        {{ $actualDays }}/{{ $payroll->required_working_days }}
                                    </span>
                                @else
                                    <span class="text-danger fw-semibold">
                                        {{ $actualDays }}/{{ $payroll->required_working_days }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($payroll->overtime_days > 0)
                                    <span class="text-primary fw-semibold">
                                        +{{ $payroll->overtime_days }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>

                            {{-- Cột Nghỉ phép + Số tiền bên dưới --}}
                            <td class="text-center">
                                @if(($paidLeaveDays > 0) || ($payroll->unpaid_leave_days > 0))
                                    <div>
                                        <span class="badge text-bg-info text-dark" title="Phép năm / Không lương">
                                            {{ $paidLeaveDays }} / {{ $payroll->unpaid_leave_days ?? 0 }} ngày
                                        </span>
                                    </div>
                                    @if($paidLeaveSalary > 0)
                                        <small class="text-success fw-semibold d-block mt-1">
                                            +{{ number_format($paidLeaveSalary) }}
                                        </small>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>

                            <td class="text-center">
                                {{ number_format($payroll->overtime_hours, 2) }}
                            </td>

                            {{-- TÁCH RIÊNG: Lương ngày công đi làm thực tế --}}
                            <td class="text-end fw-semibold">
                                {{ number_format($actualWorkingSalary) }}
                            </td>

                            {{-- TÁCH RIÊNG: Lương ngày nghỉ phép có hưởng lương --}}
                            <td class="text-end">
                                @if($paidLeaveSalary > 0)
                                    <span class="text-success fw-semibold">
                                        {{ number_format($paidLeaveSalary) }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>

                            <td class="text-end">
                                @if($payroll->overtime_day_salary > 0)
                                    <span class="text-primary fw-semibold">
                                        {{ number_format($payroll->overtime_day_salary) }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>

                            <td class="text-end">
                                @if($payroll->overtime_hour_salary > 0)
                                    <span class="text-primary fw-semibold">
                                        {{ number_format($payroll->overtime_hour_salary) }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>

                            <td class="text-end">
                                @if($payroll->overtime_salary > 0)
                                    <span class="text-primary fw-semibold">
                                        {{ number_format($payroll->overtime_salary) }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>

                            <td class="text-end">
                                {{ number_format($payroll->allowance) }}
                            </td>

                            <td class="text-end">
                                @if($payroll->bonus > 0)
                                    <span class="text-success fw-semibold">
                                        {{ number_format($payroll->bonus) }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>

                            <td class="text-end text-danger">
                                {{ number_format($payroll->insurance) }}
                            </td>

                            <td class="text-end">
                                @if($payroll->tax > 0)
                                    <span class="text-danger">
                                        {{ number_format($payroll->tax) }}
                                    </span>
                                @else
                                    0
                                @endif
                            </td>

                            <td class="text-end">
                                @if(($payroll->late_penalty_fee ?? 0) > 0)
                                    <span class="text-danger fw-semibold">
                                        − {{ number_format($payroll->late_penalty_fee) }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>

                            <td class="text-end">
                                <span class="fw-bold text-success fs-5">
                                    {{ number_format($payroll->total_salary) }} VNĐ
                                </span>
                            </td>

                            <td class="text-center">
                                <div class="d-inline-flex align-items-center gap-2 flex-wrap justify-content-center">
                                    <a href="{{ route('payroll.show', $payroll) }}" class="btn btn-sm btn-outline-primary" title="Xem chi tiết">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    @php
                                        $user = auth()->user();
                                        $canHrReview = $workflow->actorCanReview($user, $payroll);
                                        $canFinalApprove = $workflow->actorCanFinalApprove($user, $payroll);
                                        $canPay = $user->is_accountant && $workflow->canPay($payroll);
                                    @endphp

                                    @if($canHrReview)
                                        <form method="POST" action="{{ route('payroll.review', $payroll) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="Kiểm tra dữ liệu" onclick="return confirm('Xác nhận đã kiểm tra dữ liệu nhân sự trên bảng lương của {{ optional($payroll->employee)->name }}?')">
                                                Kiểm tra dữ liệu
                                            </button>
                                        </form>
                                    @elseif($canFinalApprove)
                                        <form method="POST" action="{{ route('payroll.approve', $payroll) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="Phê duyệt cuối" onclick="return confirm('Phê duyệt cuối bảng lương của {{ optional($payroll->employee)->name }}?')">
                                                Phê duyệt cuối
                                            </button>
                                        </form>
                                    @elseif($workflow->isCalculated($payroll->status))
                                        <span class="badge text-bg-secondary text-wrap" style="max-width:160px;">Chờ HR kiểm tra</span>
                                    @elseif($workflow->isHrChecked($payroll->status))
                                        <span class="badge text-bg-info text-wrap" style="max-width:160px;">Chờ phê duyệt cuối</span>
                                    @elseif($payroll->status === 'payroll_issue' || $payroll->confirmation_status === 'issue_reported')
                                        <span class="badge text-bg-danger text-wrap" style="max-width:160px;" title="{{ $payroll->issue_report }}">Sự cố lương</span>
                                        @if(auth()->user()->is_hr)
                                            <a href="{{ route('payroll.issues.fix_form', $payroll) }}" class="btn btn-sm btn-danger">Khắc phục</a>
                                        @endif
                                    @elseif($workflow->isDirectorApproved($payroll->status))
                                        <span class="badge text-bg-warning text-wrap" style="max-width:160px;">Chờ xác nhận của nhân viên</span>
                                    @elseif($canPay)
                                        <a href="{{ route('payroll.payment.show', $payroll) }}" class="btn btn-sm btn-pay-soft">Thanh toán</a>
                                    @elseif($payroll->status === 'paid')
                                        <span class="badge text-bg-success">Đã thanh toán</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="20" class="text-center py-5 text-muted">
                                @if(!empty($paymentFocus))
                                    Không có phiếu nhân viên đã xác nhận chờ thanh toán trong kỳ này.
                                @else
                                    Chưa có dữ liệu bảng lương.
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.btn-pay-soft{
    background:#bbf7d0 !important;
    color:#166534 !important;
    border:1px solid #86efac !important;
    font-weight:700;
}
.btn-pay-soft:hover{
    background:#86efac !important;
    color:#14532d !important;
}

body{
    background:#f5f7fb;
}

.card{
    border:none;
    border-radius:14px;
    overflow:hidden;
}

.card-body{
    padding:1.25rem;
}

.table{
    margin-bottom:0;
}

.payroll-table thead{
    background:#f8f9fa;
}

.payroll-table thead th{
    font-size:13px;
    font-weight:700;
    color:#6c757d;
    text-transform:uppercase;
    letter-spacing:.5px;
    padding:15px 12px;
    border-bottom:2px solid #e9ecef;
    white-space:nowrap;
}

.payroll-table tbody td{
    padding:15px 12px;
    vertical-align:middle;
    white-space:nowrap;
    border-color:#f1f3f5;
    font-size:14px;
}

.payroll-table tbody tr{
    transition:all .18s ease;
}

.payroll-table tbody tr:hover{
    background:#f8fbff;
}

.payroll-table tbody tr:hover td{
    background:#f8fbff;
}

.badge{
    font-size:13px;
    font-weight:500;
    padding:6px 12px;
    border-radius:6px;
}

.btn{
    border-radius:10px;
    font-weight:600;
}

.form-control,
.form-select{
    border-radius:10px;
    min-height:42px;
}

.text-end{
    font-variant-numeric:tabular-nums;
}

.text-success{
    font-weight:600;
    color:#198754!important;
}

.text-danger{
    font-weight:600;
    color:#dc3545!important;
}

.text-primary{
    color:#0d6efd!important;
}

.text-muted{
    font-size:14px;
    color:#6c757d!important;
}

.table-responsive{
    border-radius:12px;
}

h3{
    color:#212529;
}
</style>

@endsection