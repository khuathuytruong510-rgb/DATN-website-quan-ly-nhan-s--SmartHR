@extends('layouts.app')

@section('content')

<div class="container-fluid">

    {{-- Header --}}
    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center flex-wrap">

                <div>
                    <h3 class="fw-bold mb-1">
                        Bảng lương nhân viên
                    </h3>

                    <p class="text-muted mb-0">
                        Quản lý và tính lương nhân viên theo tháng.
                    </p>
                </div>

                @php
                    $canBulkApprove = auth()->user()->is_admin || auth()->user()->is_hr;
                    $pendingCount = $payrolls->where('status', 'pending')->count();
                @endphp

                <div class="d-flex flex-column gap-2 align-items-stretch" style="min-width:min(520px,100%);">
                    <form method="POST" action="{{ route('payroll.generate') }}">
                        @csrf
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
                                <button class="btn btn-primary">
                                    <i class="bi bi-calculator"></i>
                                    Tính lương
                                </button>
                                <a class="btn btn-outline-secondary"
                                   href="{{ route('payroll.print', ['month' => $month, 'year' => $year]) }}"
                                   target="_blank">
                                    <i class="bi bi-printer"></i>
                                    In bảng lương
                                </a>
                            </div>
                        </div>
                    </form>

                    @if($canBulkApprove)
                        <form method="POST" action="{{ route('payroll.approve_all') }}"
                              onsubmit="return confirm('Duyệt toàn bộ {{ $pendingCount }} bảng lương đang chờ duyệt tháng {{ sprintf('%02d/%d', $month, $year) }}? Hệ thống sẽ gửi xác nhận cho nhân viên.');">
                            @csrf
                            <input type="hidden" name="month" value="{{ $month }}">
                            <input type="hidden" name="year" value="{{ $year }}">
                            <button type="submit" class="btn btn-success"
                                    @disabled($pendingCount < 1)
                                    title="{{ $pendingCount < 1 ? 'Không có phiếu chờ duyệt' : 'Duyệt tất cả phiếu pending' }}">
                                <i class="bi bi-check2-all"></i>
                                Duyệt toàn bộ
                                @if($pendingCount > 0)
                                    ({{ $pendingCount }})
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

                            <th class="text-center">TC Giờ</th>

                            <th class="text-end">Lương công</th>

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

<tr>

    <td>
        <div class="fw-semibold">
            {{ $payroll->employee->name }}
        </div>
    </td>

    <td>

       @switch($payroll->employee->position)

            @case('Giám Đốc')
                <span class="badge text-bg-dark">
                    Giám đốc
                </span>
                @break

            @case('Trưởng Phòng Nhân Sự')
                <span class="badge text-bg-primary">
                    Trưởng phòng
                </span>
                @break

            @default
                <span class="badge text-bg-light border text-dark">
                    Nhân viên
                </span>

        @endswitch

    </td>

    <td class="text-center">
        {{ sprintf('%02d', $payroll->month) }}/{{ $payroll->year }}
    </td>

    <td class="text-end">
        {{ number_format($payroll->base_salary) }}
    </td>

    <td class="text-center">

        @if($payroll->working_days > $payroll->required_working_days)

            <span class="text-primary fw-semibold">
                {{ $payroll->working_days }}/{{ $payroll->required_working_days }}
            </span>

        @elseif($payroll->working_days == $payroll->required_working_days)

            <span class="text-success fw-semibold">
        {{ $payroll->working_days }}/{{ $payroll->required_working_days }}
    </span>

@else

    <span class="text-danger fw-semibold">
        {{ $payroll->working_days }}/{{ $payroll->required_working_days }}
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

    <td class="text-center">

        {{ number_format($payroll->overtime_hours,2) }}

    </td>

    <td class="text-end">

        {{ number_format($payroll->working_salary) }}

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

        @if($payroll->bonus>0)

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

        @if($payroll->tax>0)

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
                $canApprove = ($user->is_admin || $user->is_hr) && $payroll->status === 'pending';
                $canPay = ($user->is_admin || $user->is_accountant) && $payroll->status === 'ready_for_payment';
            @endphp

            @if($canApprove)
                <form method="POST" action="{{ route('payroll.approve', $payroll) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success" title="Duyệt" onclick="return confirm('Duyệt bảng lương của {{ optional($payroll->employee)->name }}?')">
                        Duyệt
                    </button>
                </form>
            @elseif($payroll->confirmation_status === 'issue_reported')
                <span class="badge text-bg-danger text-wrap" style="max-width:160px;" title="{{ $payroll->issue_report }}">Có sự cố</span>
                @if(auth()->user()->is_admin || auth()->user()->is_hr || auth()->user()->is_accountant)
                    <a href="{{ route('payroll.issues.fix_form', $payroll) }}" class="btn btn-sm btn-danger">Khắc phục</a>
                @endif
            @elseif(in_array($payroll->status, ['waiting_confirmation', 'approved'], true))
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

    <td colspan="17" class="text-center py-5 text-muted">

        Chưa có dữ liệu bảng lương.

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
}

.text-danger{
    font-weight:600;
}

.table-responsive{
    border-radius:12px;
}

h3{
    color:#212529;
}

.text-muted{
    font-size:14px;
}
.text-success{
    color:#198754!important;
}

.text-primary{
    color:#0d6efd!important;
}

.text-danger{
    color:#dc3545!important;
}

.text-muted{
    color:#6c757d!important;
}

</style>

@endsection