@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div>
                <h3 class="fw-bold mb-1">
                    <i class="bi bi-file-earmark-plus"></i> Tạo phiếu thanh toán
                </h3>
                <p class="text-muted mb-0">
                    Chọn bảng lương để tạo phiếu thanh toán cho nhân viên.
                </p>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('salary_payments.select_payroll') }}" class="row g-3">
                <div class="col-md-3">
                    <label>Tháng</label>
                    <select name="month" class="form-select">
                        @for($i=1;$i<=12;$i++)
                            <option value="{{ $i }}" {{ request('month', now()->month)==$i?'selected':'' }}>
                                Tháng {{ $i }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="col-md-3">
                    <label>Năm</label>
                    <select name="year" class="form-select">
                        @for($y=2025;$y<=2035;$y++)
                            <option value="{{ $y }}" {{ request('year', now()->year)==$y?'selected':'' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Tìm kiếm
                    </button>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <a href="{{ route('salary_payments.index') }}" class="btn btn-secondary w-100">
                        <i class="bi bi-arrow-left"></i> Quay lại
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Payroll List --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nhân viên</th>
                            <th>Chức vụ</th>
                            <th>Phòng ban</th>
                            <th class="text-center">Ngày công</th>
                            <th class="text-end">Lương cơ bản</th>
                            <th class="text-end">Lương công</th>
                            <th class="text-end">Tổng TC</th>
                            <th class="text-end">Thưởng</th>
                            <th class="text-end">Thực nhận</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payrolls as $payroll)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $payroll->employee->name }}</div>
                            </td>
                            <td>{{ $payroll->employee->position ?? '-' }}</td>
                            <td>{{ $payroll->employee->department->name ?? '-' }}</td>
                            <td class="text-center">
                                <span class="badge text-bg-light border text-dark">
                                    {{ $payroll->working_days }}/{{ $payroll->required_working_days }}
                                </span>
                            </td>
                            <td class="text-end">{{ number_format($payroll->base_salary, 0) }}</td>
                            <td class="text-end">{{ number_format($payroll->working_salary, 0) }}</td>
                            <td class="text-end">
                                @if($payroll->overtime_salary > 0)
                                    <span class="text-primary fw-semibold">{{ number_format($payroll->overtime_salary, 0) }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-end">
                                @if($payroll->bonus > 0)
                                    <span class="text-success fw-semibold">{{ number_format($payroll->bonus, 0) }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-end">
                                <span class="fw-bold text-success fs-6">{{ number_format($payroll->total_salary, 0) }} VNĐ</span>
                            </td>
                            <td class="text-center">
                                <span class="badge text-bg-success">
                                    <i class="bi bi-check-circle"></i> Đã duyệt
                                </span>
                            </td>
                            <td class="text-center">
                                <form method="POST" action="{{ route('salary_payments.create', $payroll) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary" title="Tạo phiếu thanh toán">
                                        <i class="bi bi-plus-circle"></i> Tạo phiếu
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                <p class="mt-3">Không có bảng lương nào chưa tạo phiếu thanh toán.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $payrolls->links() }}
    </div>
</div>

<style>
body {
    background: #f5f7fb;
}

.card {
    border: none;
    border-radius: 14px;
    overflow: hidden;
}

.card-body {
    padding: 1.25rem;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
}
</style>
@endsection
