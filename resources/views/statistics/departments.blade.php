@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <h1 class="mb-0">Báo cáo lương theo phòng ban</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('statistics.departments.export', ['month' => $month, 'year' => $year]) }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-download"></i> Xuất CSV
            </a>
            <a href="{{ route('statistics.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Quay lại
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
                    <button class="btn btn-sm btn-primary">Xem</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <canvas id="deptChart" height="80"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Phòng ban</th>
                            <th class="text-center">Số NV</th>
                            <th class="text-end">Tổng lương thực nhận</th>
                            <th class="text-end">Lương TB/NV</th>
                            <th class="text-end">Lương công</th>
                            <th class="text-end">Tăng ca</th>
                            <th class="text-end">Phụ cấp</th>
                            <th class="text-end">Thưởng</th>
                            <th class="text-end">Bảo hiểm</th>
                            <th class="text-end">Thuế</th>
                            <th class="text-end">% Tổng lương</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $grandTotal = $departments->sum('total_net'); @endphp
                        @forelse($departments as $dept)
                        @php
                            $deptAvg = $dept->employee_count > 0 ? $dept->total_net / $dept->employee_count : 0;
                            $pct = $grandTotal > 0 ? ($dept->total_net / $grandTotal) * 100 : 0;
                        @endphp
                        <tr>
                            <td class="fw-medium">{{ $dept->department_name }}</td>
                            <td class="text-center">{{ $dept->employee_count }}</td>
                            <td class="text-end fw-bold">{{ number_format($dept->total_net, 0, ',', '.') }}đ</td>
                            <td class="text-end">{{ number_format($deptAvg, 0, ',', '.') }}đ</td>
                            <td class="text-end">{{ number_format($dept->total_working, 0, ',', '.') }}đ</td>
                            <td class="text-end">{{ number_format($dept->total_overtime, 0, ',', '.') }}đ</td>
                            <td class="text-end">{{ number_format($dept->total_allowance, 0, ',', '.') }}đ</td>
                            <td class="text-end">{{ number_format($dept->total_bonus, 0, ',', '.') }}đ</td>
                            <td class="text-end text-danger">{{ number_format($dept->total_insurance, 0, ',', '.') }}đ</td>
                            <td class="text-end text-danger">{{ number_format($dept->total_tax, 0, ',', '.') }}đ</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 16px;">
                                        <div class="progress-bar bg-primary" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="small">{{ round($pct, 1) }}%</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">Không có dữ liệu</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-dark">
                        <tr>
                            <th>Tổng cộng</th>
                            <th class="text-center">{{ $departments->sum('employee_count') }}</th>
                            <th class="text-end">{{ number_format($grandTotal, 0, ',', '.') }}đ</th>
                            <th class="text-end">{{ $departments->sum('employee_count') > 0 ? number_format($grandTotal / $departments->sum('employee_count'), 0, ',', '.') : 0 }}đ</th>
                            <th class="text-end">{{ number_format($departments->sum('total_working'), 0, ',', '.') }}đ</th>
                            <th class="text-end">{{ number_format($departments->sum('total_overtime'), 0, ',', '.') }}đ</th>
                            <th class="text-end">{{ number_format($departments->sum('total_allowance'), 0, ',', '.') }}đ</th>
                            <th class="text-end">{{ number_format($departments->sum('total_bonus'), 0, ',', '.') }}đ</th>
                            <th class="text-end">{{ number_format($departments->sum('total_insurance'), 0, ',', '.') }}đ</th>
                            <th class="text-end">{{ number_format($departments->sum('total_tax'), 0, ',', '.') }}đ</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('deptChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($departments->pluck('department_name')),
            datasets: [
                { label: 'Lương thực nhận', data: @json($departments->pluck('total_net')->map(fn($v) => round($v / 1000000, 1))), backgroundColor: '#0d6efd', borderRadius: 4 },
                { label: 'Tăng ca (triệu)', data: @json($departments->pluck('total_overtime')->map(fn($v) => round($v / 1000000, 1))), backgroundColor: '#ffc107', borderRadius: 4 },
                { label: 'Bảo hiểm (triệu)', data: @json($departments->pluck('total_insurance')->map(fn($v) => round($v / 1000000, 1))), backgroundColor: '#dc3545', borderRadius: 4 }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true, title: { display: true, text: 'Triệu VNĐ' } } }
        }
    });
});
</script>
@endpush
@endsection
