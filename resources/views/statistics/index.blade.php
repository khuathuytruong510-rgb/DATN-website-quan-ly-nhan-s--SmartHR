@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3 flex-wrap gap-2">
        <h1 class="mb-0">Thống kê & Báo cáo lương</h1>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('statistics.export', ['month' => $month, 'year' => $year]) }}" class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Xuất Excel
            </a>
            <a href="{{ route('statistics.trend', ['months' => 12]) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-graph-up"></i> Xu hướng
            </a>
            <a href="{{ route('statistics.departments') }}" class="btn btn-outline-info btn-sm">
                <i class="bi bi-building"></i> Theo phòng ban
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label mb-0 small">Tháng</label>
                    <select name="month" class="form-select form-select-sm">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>Tháng {{ $m }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label mb-0 small">Năm</label>
                    <select name="year" class="form-select form-select-sm">
                        @for($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-primary">Xem thống kê</button>
                </div>
                <div class="col-auto">
                    <a id="stats-export-link"
                       href="{{ route('statistics.export', ['month' => $month, 'year' => $year]) }}"
                       class="btn btn-sm btn-outline-success">
                        <i class="bi bi-download"></i> Xuất Excel tháng đang chọn
                    </a>
                </div>
            </form>
            <script>
                (function () {
                    const form = document.currentScript.previousElementSibling;
                    const link = document.getElementById('stats-export-link');
                    if (!form || !link) return;
                    const sync = () => {
                        const month = form.querySelector('[name=month]')?.value || '{{ $month }}';
                        const year = form.querySelector('[name=year]')?.value || '{{ $year }}';
                        link.href = @json(url('/statistics/export')) + '?month=' + encodeURIComponent(month) + '&year=' + encodeURIComponent(year);
                    };
                    form.addEventListener('change', sync);
                    sync();
                })();
            </script>
        </div>
    </div>

    {{-- Overview Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-primary h-100">
                <div class="card-body text-center">
                    <div class="text-primary fs-2 fw-bold">{{ number_format($overview['totalNetSalary'], 0, ',', '.') }}đ</div>
                    <div class="text-muted small">Tổng lương thực nhận</div>
                    <div class="small mt-1">
                        @php $change = $comparison['total_change'] ?? 0; @endphp
                        @if($change != 0)
                            <span class="text-{{ $change > 0 ? 'success' : 'danger' }}">
                                <i class="bi bi-arrow-{{ $change > 0 ? 'up' : 'down' }}"></i> {{ abs($change) }}%
                            </span>
                            vs tháng trước
                        @else
                            <span class="text-muted">— tháng trước</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success h-100">
                <div class="card-body text-center">
                    <div class="text-success fs-2 fw-bold">{{ $overview['totalEmployees'] }}</div>
                    <div class="text-muted small">Nhân viên có lương</div>
                    <div class="small mt-1">
                        @php $empChange = $comparison['employee_change'] ?? 0; @endphp
                        @if($empChange != 0)
                            <span class="text-{{ $empChange > 0 ? 'success' : 'danger' }}">{{ ($empChange > 0 ? '+' : '') . $empChange }}%</span>
                            vs tháng trước
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info h-100">
                <div class="card-body text-center">
                    @php $avgSalary = $overview['totalEmployees'] > 0 ? $overview['totalNetSalary'] / $overview['totalEmployees'] : 0; @endphp
                    <div class="text-info fs-2 fw-bold">{{ number_format($avgSalary, 0, ',', '.') }}đ</div>
                    <div class="text-muted small">Lương trung bình</div>
                    <div class="small mt-1">
                        @php $avgChange = $comparison['avg_change'] ?? 0; @endphp
                        @if($avgChange != 0)
                            <span class="text-{{ $avgChange > 0 ? 'success' : 'danger' }}">{{ ($avgChange > 0 ? '+' : '') . $avgChange }}%</span>
                            vs tháng trước
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning h-100">
                <div class="card-body text-center">
                    <div class="text-warning fs-2 fw-bold">{{ number_format($overview['totalOvertime'], 0, ',', '.') }}đ</div>
                    <div class="text-muted small">Tổng tăng ca</div>
                    <div class="small mt-1">
                        @php $otPct = $overview['totalNetSalary'] > 0 ? round(($overview['totalOvertime'] / $overview['totalNetSalary']) * 100, 1) : 0; @endphp
                        {{ $otPct }}% tổng lương
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Second row cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="fs-4 fw-bold text-success">{{ number_format($overview['paidAmount'], 0, ',', '.') }}đ</div>
                    <div class="text-muted small">Đã thanh toán</div>
                    <span class="badge bg-success">{{ $overview['paidCount'] }} phiếu</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="fs-4 fw-bold text-warning">{{ number_format($overview['pendingAmount'], 0, ',', '.') }}đ</div>
                    <div class="text-muted small">Chờ thanh toán</div>
                    <span class="badge bg-warning text-dark">{{ $overview['pendingCount'] }} phiếu</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="fs-4 fw-bold text-primary">{{ number_format($overview['totalInsurance'], 0, ',', '.') }}đ</div>
                    <div class="text-muted small">Tổng bảo hiểm</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="fs-4 fw-bold text-danger">{{ number_format($overview['totalTax'], 0, ',', '.') }}đ</div>
                    <div class="text-muted small">Tổng thuế TNCN</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Salary Distribution Chart --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0">Phân bố lương</h6></div>
                <div class="card-body">
                    <canvas id="distributionChart" height="250"></canvas>
                </div>
            </div>
        </div>

        {{-- Salary Structure --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0">Cơ cấu lương</h6></div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small">Lương công</span>
                            <span class="small fw-bold">{{ number_format($overview['totalWorkingSalary'], 0, ',', '.') }}đ</span>
                        </div>
                        @php $wsPct = $overview['totalNetSalary'] > 0 ? ($overview['totalWorkingSalary'] / $overview['totalNetSalary']) * 100 : 0; @endphp
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-primary" style="width: {{ $wsPct }}%">{{ round($wsPct, 1) }}%</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small">Tăng ca</span>
                            <span class="small fw-bold">{{ number_format($overview['totalOvertime'], 0, ',', '.') }}đ</span>
                        </div>
                        @php $otPct = $overview['totalNetSalary'] > 0 ? ($overview['totalOvertime'] / $overview['totalNetSalary']) * 100 : 0; @endphp
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-warning" style="width: {{ $otPct }}%">{{ round($otPct, 1) }}%</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small">Phụ cấp</span>
                            <span class="small fw-bold">{{ number_format($overview['totalAllowance'], 0, ',', '.') }}đ</span>
                        </div>
                        @php $alPct = $overview['totalNetSalary'] > 0 ? ($overview['totalAllowance'] / $overview['totalNetSalary']) * 100 : 0; @endphp
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-info" style="width: {{ $alPct }}%">{{ round($alPct, 1) }}%</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small">Thưởng</span>
                            <span class="small fw-bold">{{ number_format($overview['totalBonus'], 0, ',', '.') }}đ</span>
                        </div>
                        @php $boPct = $overview['totalNetSalary'] > 0 ? ($overview['totalBonus'] / $overview['totalNetSalary']) * 100 : 0; @endphp
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-success" style="width: {{ $boPct }}%">{{ round($boPct, 1) }}%</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small text-danger">Bảo hiểm</span>
                            <span class="small fw-bold text-danger">-{{ number_format($overview['totalInsurance'], 0, ',', '.') }}đ</span>
                        </div>
                        @php $inPct = $overview['totalNetSalary'] > 0 ? ($overview['totalInsurance'] / $overview['totalNetSalary']) * 100 : 0; @endphp
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-danger" style="width: {{ $inPct }}%">{{ round($inPct, 1) }}%</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small text-danger">Thuế TNCN</span>
                            <span class="small fw-bold text-danger">-{{ number_format($overview['totalTax'], 0, ',', '.') }}đ</span>
                        </div>
                        @php $txPct = $overview['totalNetSalary'] > 0 ? ($overview['totalTax'] / $overview['totalNetSalary']) * 100 : 0; @endphp
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-danger" style="width: {{ $txPct }}%">{{ round($txPct, 1) }}%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Department Breakdown --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Lương theo phòng ban</h6>
                    <a href="{{ route('statistics.departments.export', ['month' => $month, 'year' => $year]) }}" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-download"></i> Xuất CSV
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Phòng ban</th>
                                    <th class="text-center">NV</th>
                                    <th class="text-end">Tổng lương</th>
                                    <th class="text-end">Lương TB</th>
                                    <th class="text-end">Tăng ca</th>
                                    <th class="text-end">Bảo hiểm</th>
                                    <th class="text-end">Thuế</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($departments as $dept)
                                @php $deptAvg = $dept->employee_count > 0 ? $dept->total_net / $dept->employee_count : 0; @endphp
                                <tr>
                                    <td class="fw-medium">{{ $dept->department_name }}</td>
                                    <td class="text-center">{{ $dept->employee_count }}</td>
                                    <td class="text-end">{{ number_format($dept->total_net, 0, ',', '.') }}đ</td>
                                    <td class="text-end">{{ number_format($deptAvg, 0, ',', '.') }}đ</td>
                                    <td class="text-end">{{ number_format($dept->total_overtime, 0, ',', '.') }}đ</td>
                                    <td class="text-end text-danger">{{ number_format($dept->total_insurance, 0, ',', '.') }}đ</td>
                                    <td class="text-end text-danger">{{ number_format($dept->total_tax, 0, ',', '.') }}đ</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Không có dữ liệu</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Top Earners --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0">Top 10 lương cao nhất</h6></div>
                <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                    @forelse($topEarners as $i => $p)
                    <div class="d-flex align-items-center px-3 py-2 {{ $i > 0 ? 'border-top' : '' }}">
                        <span class="badge bg-{{ $i < 3 ? 'warning text-dark' : 'secondary' }} me-2" style="width:24px">{{ $i + 1 }}</span>
                        <div class="flex-grow-1">
                            <div class="fw-medium small">{{ $p->employee->name ?? 'N/A' }}</div>
                            <div class="text-muted" style="font-size:0.75rem">{{ $p->employee->department->name ?? '' }}</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold small text-success">{{ number_format($p->total_salary, 0, ',', '.') }}đ</div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-4">Không có dữ liệu</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Payment Methods --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0">Phương thức thanh toán</h6></div>
                <div class="card-body">
                    <canvas id="paymentMethodChart" height="200"></canvas>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between">
                            <span><i class="bi bi-bank text-primary"></i> Chuyển khoản</span>
                            <span class="fw-bold">{{ $overview['bankCount'] }} phiếu</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span><i class="bi bi-cash text-success"></i> Tiền mặt</span>
                            <span class="fw-bold">{{ $overview['cashCount'] }} phiếu</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reconciliation Status --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0">Trạng thái đối soát</h6></div>
                <div class="card-body">
                    <canvas id="reconChart" height="200"></canvas>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between">
                            <span><span class="badge bg-success"></span> Đã đối soát</span>
                            <span class="fw-bold">{{ $overview['reconciled'] }} phiếu</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span><span class="badge bg-danger"></span> Sai lệch</span>
                            <span class="fw-bold">{{ $overview['discrepancy'] }} phiếu</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Distribution Chart
    const distCtx = document.getElementById('distributionChart');
    if (distCtx) {
        new Chart(distCtx, {
            type: 'bar',
            data: {
                labels: @json(array_column($distribution, 'label')),
                datasets: [{
                    label: 'Số nhân viên',
                    data: @json(array_column($distribution, 'count')),
                    backgroundColor: ['#198754','#0d6efd','#ffc107','#fd7e14','#dc3545','#6f42c1'],
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }

    // Payment Method Chart
    const pmCtx = document.getElementById('paymentMethodChart');
    if (pmCtx) {
        new Chart(pmCtx, {
            type: 'doughnut',
            data: {
                labels: ['Chuyển khoản', 'Tiền mặt'],
                datasets: [{
                    data: [{{ $overview['bankCount'] }}, {{ $overview['cashCount'] }}],
                    backgroundColor: ['#0d6efd', '#198754']
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    }

    // Reconciliation Chart
    const reconCtx = document.getElementById('reconChart');
    if (reconCtx) {
        new Chart(reconCtx, {
            type: 'doughnut',
            data: {
                labels: ['Đã KS', 'Sai lệch', 'Chưa KS'],
                datasets: [{
                    data: [{{ $overview['reconciled'] }}, {{ $overview['discrepancy'] }}, {{ $overview['pendingCount'] }}],
                    backgroundColor: ['#198754', '#dc3545', '#6c757d']
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    }
});
</script>
@endpush
@endsection
