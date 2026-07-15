@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="card shadow-lg border-0 rounded-4">

        <div class="card-header bg-white border-0 pt-4 px-4">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h3 class="mb-1 fw-bold text-secondary">
                        💰 BẢNG LƯƠNG NHÂN VIÊN
                    </h3>

                    <small class="text-muted">
                        Hệ thống quản lý tiền lương SmartHR
                    </small>

                </div>

                <form action="{{ route('payroll.generate') }}" method="POST" class="d-flex align-items-center gap-3">

                    @csrf

                    <div class="d-flex align-items-center gap-2">
                        <label for="month" class="mb-0 text-secondary">Chọn tháng</label>
                        <input type="month" id="month" name="month" class="form-control" value="{{ old('month', $selectedMonth ?? now()->format('Y-m')) }}" style="max-width: 180px;" required>
                    </div>

                    <button class="btn btn-light border fw-bold px-4 shadow-sm">

                        <i class="fas fa-calculator me-2"></i>

                        Tính lương

                    </button>

                </form>

            </div>

        </div>

        <div class="card-body px-4">

            <div class="table-responsive">

                <table class="table table-hover align-middle custom-payroll-table">

                    <thead class="text-secondary text-uppercase fs-7 fw-bold">

                        <tr>

                            <th class="text-start">👤 Nhân viên</th>

                            <th class="text-center">💼 Chức vụ</th>

                            <th class="text-center">📅 Tháng</th>

                            <th class="text-center">💵 Lương cơ bản</th>

                            <th class="text-center">📌 Công</th>

                            <th class="text-center">📆 Ngày TC</th>

                            <th class="text-center">⏰ Giờ TC</th>

                            <th class="text-end">💰 Lương ngày</th>

                            <th class="text-end">TC Ngày</th>

                            <th class="text-end">TC Giờ</th>

                            <th class="text-end">Tổng TC</th>

                            <th class="text-end">Phụ cấp</th>

                            <th class="text-end">Thưởng</th>

                            <th class="text-center">BH (10.5%)</th>

                            <th class="text-end">Thuế</th>

                            <th class="text-end fw-bold">Thực nhận</th>

                        </tr>

                    </thead>

                    <tbody>

                    @forelse($payrolls as $payroll)

                    <tr>

                        <td class="fw-semibold text-dark text-start">

                            {{ $payroll->employee->name }}

                        </td>

                        <td class="text-center">

                            @if($payroll->employee->position == 'Giám Đốc')

                                <span class="badge bg-danger rounded-pill px-3 py-2">
                                    👑 Giám Đốc
                                </span>

                            @elseif($payroll->employee->position == 'Trưởng Phòng Nhân Sự')

                                <span class="badge bg-warning text-dark rounded-pill px-3 py-2">
                                    👨‍💼 Trưởng phòng HR
                                </span>

                            @else

                                <span class="badge bg-info rounded-pill px-3 py-2">
                                    👨‍💻 Nhân viên
                                </span>

                            @endif

                        </td>

                        <td class="text-center text-muted">

                            {{ $payroll->display_month }}

                        </td>

                        <td class="text-center">

                            <span class="badge bg-info-subtle text-info-emphasis rounded-pill px-3 py-2 fw-bold">

                                {{ number_format($payroll->base_salary) }}

                            </span>

                        </td>
                                                <td class="text-center">

                            @if($payroll->working_days < $payroll->required_working_days)

                                <span class="badge bg-danger-subtle text-danger-emphasis rounded-pill px-3 py-2 fw-bold">

                                    {{ $payroll->working_days }}/{{ $payroll->required_working_days }}

                                </span>

                            @elseif($payroll->overtime_days > 0)

                                <span class="badge bg-primary-subtle text-primary-emphasis rounded-pill px-3 py-2 fw-bold">

                                    {{ $payroll->required_working_days }}/{{ $payroll->required_working_days }} ⭐

                                </span>

                            @else

                                <span class="badge bg-success-subtle text-success-emphasis rounded-pill px-3 py-2 fw-bold">

                                    {{ $payroll->required_working_days }}/{{ $payroll->required_working_days }}

                                </span>

                            @endif

                        </td>

                        <td class="text-center">

                            @if($payroll->overtime_days > 0)

                                <span class="badge bg-info-subtle text-info-emphasis rounded-pill px-3 py-2 fw-semibold">

                                    +{{ $payroll->overtime_days }}

                                </span>

                            @else

                                <span class="badge bg-light text-muted rounded-pill px-3 py-2">

                                    0

                                </span>

                            @endif

                        </td>

                        <td class="text-center">

                            <span class="badge bg-primary-subtle text-primary-emphasis rounded-pill px-3 py-2 fw-semibold">

                                {{ number_format($payroll->overtime_hours,2) }}

                            </span>

                        </td>

                        <td class="text-end fw-semibold">

                            {{ number_format($payroll->working_salary) }}

                        </td>

                        <td class="text-end">

                            @if($payroll->overtime_day_salary > 0)

                                <span class="text-danger fw-bold">

                                    +{{ number_format($payroll->overtime_day_salary) }}

                                </span>

                            @else

                                -

                            @endif

                        </td>

                        <td class="text-end">

                            @if($payroll->overtime_hour_salary > 0)

                                <span class="text-warning fw-bold">

                                    +{{ number_format($payroll->overtime_hour_salary) }}

                                </span>

                            @else

                                -

                            @endif

                        </td>

                        <td class="text-end">

                            <span class="badge bg-info-subtle text-info-emphasis rounded-pill px-3 py-2 fw-bold">

                                {{ number_format($payroll->overtime_salary) }}

                            </span>

                        </td>

                        <td class="text-end">

                            <span class="badge bg-success-subtle text-success-emphasis rounded-pill px-3 py-2 fw-bold">

                                {{ number_format($payroll->allowance) }}

                            </span>

                        </td>

                        <td class="text-end">

                            @if($payroll->bonus > 0)

                                <span class="badge bg-success rounded-pill px-3 py-2">

                                    🎉 {{ number_format($payroll->bonus) }}

                                </span>

                            @else

                                -

                            @endif

                        </td>

                        <td class="text-center">

                            <span class="badge bg-danger-subtle text-danger-emphasis rounded-pill px-3 py-2 fw-bold">

                                {{ number_format($payroll->insurance) }}

                            </span>

                        </td>

                        <td class="text-end">

                            @if($payroll->tax > 0)

                                <span class="badge bg-danger rounded-pill px-3 py-2">

                                    {{ number_format($payroll->tax) }}

                                </span>

                            @else

                                <span class="badge bg-success rounded-pill px-3 py-2">

                                    0

                                </span>

                            @endif

                        </td>

                        <td class="text-end">

                            <span class="badge bg-primary rounded-pill px-3 py-2 fs-6 fw-bold">

                                {{ number_format($payroll->total_salary) }}

                            </span>

                        </td>

                    </tr>
                                            @empty

                            <tr>

                                <td colspan="16" class="text-center py-5 text-muted">

                                    Không có dữ liệu bảng lương cho tháng này.

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

.custom-payroll-table{
    border-collapse:separate;
    border-spacing:0 8px;
}

.custom-payroll-table th{
    font-size:.82rem;
    background:#f8f9fa;
    color:#495057;
    font-weight:700;
    text-transform:uppercase;
    padding:14px 10px;
    border-bottom:2px solid #dee2e6;
    white-space:nowrap;
}

.custom-payroll-table td{
    padding:14px 10px;
    font-size:.92rem;
    background:#fff;
    border-bottom:1px solid #f3f3f3;
    vertical-align:middle;
    white-space:nowrap;
}

.custom-payroll-table tbody tr:hover td{
    background:#f8fbff;
}

.badge{
    font-size:.85rem;
    font-weight:600;
}

.bg-info-subtle{
    background:#e8f4fd!important;
}

.text-info-emphasis{
    color:#0d6efd!important;
}

.bg-primary-subtle{
    background:#eaf2ff!important;
}

.text-primary-emphasis{
    color:#0d6efd!important;
}

.bg-success-subtle{
    background:#e8f8ef!important;
}

.text-success-emphasis{
    color:#198754!important;
}

.bg-danger-subtle{
    background:#fdecec!important;
}

.text-danger-emphasis{
    color:#dc3545!important;
}

.card{
    overflow:hidden;
}

.card-header{
    background:linear-gradient(90deg,#0d6efd,#4f8dfd);
    color:#fff;
}

.card-header h3{
    color:#fff!important;
}

.card-header small{
    color:#f8f9fa!important;
}

.btn-light{
    transition:.2s;
}

.btn-light:hover{
    transform:translateY(-2px);
}

</style>

@endsection