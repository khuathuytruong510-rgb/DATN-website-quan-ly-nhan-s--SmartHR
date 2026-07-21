@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h3 class="fw-bold mb-1">
                        <i class="bi bi-cash-coin"></i> Lịch sử thanh toán lương
                    </h3>
                    <p class="text-muted mb-0">
                        Theo dõi các phiếu đã thanh toán. Để thanh toán mới, dùng quy trình bảng lương (sau khi NV xác nhận).
                    </p>
                </div>
                <div>
                    <a href="{{ route('payroll.index') }}" class="btn btn-primary">
                        <i class="bi bi-wallet2"></i> Đến bảng lương / thanh toán
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('salary_payments.index') }}" class="row g-3">
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

                <div class="col-md-3">
                    <label>Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="all" {{ request('status', 'all')=='all'?'selected':'' }}>Tất cả</option>
                        <option value="pending" {{ request('status', 'all')=='pending'?'selected':'' }}>Chưa thanh toán</option>
                        <option value="paid" {{ request('status', 'all')=='paid'?'selected':'' }}>Đã thanh toán</option>
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Tìm kiếm
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Mã phiếu</th>
                            <th>Nhân viên</th>
                            <th>Phòng ban</th>
                            <th class="text-center">Tháng</th>
                            <th class="text-end">Tổng lương</th>
                            <th class="text-end">Khấu trừ</th>
                            <th class="text-end">Thực lĩnh</th>
                            <th>Hình thức</th>
                            <th class="text-center">Trạng thái</th>
                            <th>Người thanh toán</th>
                            <th class="text-center">Ngày thanh toán</th>
                            <th class="text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $p)
                        <tr>
                            <td>
                                <code class="text-primary">{{ $p->code }}</code>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $p->employee->name ?? '-' }}</div>
                                <small class="text-muted">ID: {{ $p->employee->employee_code ?? '-' }}</small>
                            </td>
                            <td>{{ $p->employee->department->name ?? '-' }}</td>
                            <td class="text-center">
                                <span class="badge text-bg-light border text-dark">
                                    {{ sprintf('%02d/%04d', $p->month, $p->year) }}
                                </span>
                            </td>
                            <td class="text-end">{{ number_format($p->total, 0) }}</td>
                            <td class="text-end text-danger">{{ number_format($p->deductions, 0) }}</td>
                            <td class="text-end">
                                <span class="fw-bold text-success fs-6">{{ number_format($p->net, 0) }} VNĐ</span>
                            </td>
                            <td>
                                @if($p->payment_method)
                                    <span class="badge text-bg-{{ $p->payment_method === 'bank_transfer' ? 'info' : 'warning' }}">
                                        {{ $p->payment_method === 'bank_transfer' ? 'Chuyển khoản' : 'Tiền mặt' }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($p->status === 'paid')
                                    <span class="badge text-bg-success">
                                        <i class="bi bi-check-circle"></i> Đã TT
                                    </span>
                                @else
                                    <span class="badge text-bg-warning">
                                        <i class="bi bi-hourglass-split"></i> Chờ TT
                                    </span>
                                @endif
                            </td>
                            <td>{{ $p->paidBy->name ?? '-' }}</td>
                            <td class="text-center">
                                @if($p->paid_at)
                                    {{ $p->paid_at->format('d/m/Y') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('salary_payments.show', $p) }}" class="btn btn-outline-primary" title="Xem chi tiết">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if($p->status === 'pending')
                                        <a href="{{ route('salary_payments.edit', $p) }}" class="btn btn-outline-warning" title="Chỉnh sửa">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="12" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                <p class="mt-3">Chưa có phiếu thanh toán nào.</p>
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
        {{ $payments->links() }}
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
