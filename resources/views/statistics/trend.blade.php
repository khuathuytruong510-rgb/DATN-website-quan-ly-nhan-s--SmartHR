@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <h1 class="mb-0">Xu hướng lương 12 tháng</h1>
        <a href="{{ route('statistics.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Quay lại
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <canvas id="trendChart" height="100"></canvas>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0">Tăng ca theo tháng</h6></div>
                <div class="card-body">
                    <canvas id="overtimeChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0">Bảo hiểm & Thuế theo tháng</h6></div>
                <div class="card-body">
                    <canvas id="deductionChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h6 class="mb-0">Bảng chi tiết 12 tháng</h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tháng</th>
                            <th class="text-center">Số NV</th>
                            <th class="text-end">Tổng lương thực nhận</th>
                            <th class="text-end">Lương công</th>
                            <th class="text-end">Tăng ca</th>
                            <th class="text-end">Bảo hiểm</th>
                            <th class="text-end">Thuế</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trend as $t)
                        <tr>
                            <td class="fw-medium">{{ $t['label'] }}</td>
                            <td class="text-center">{{ $t['total_payrolls'] }}</td>
                            <td class="text-end fw-bold">{{ number_format($t['total_net'], 0, ',', '.') }}đ</td>
                            <td class="text-end">{{ number_format($t['total_working'], 0, ',', '.') }}đ</td>
                            <td class="text-end">{{ number_format($t['total_overtime'], 0, ',', '.') }}đ</td>
                            <td class="text-end text-danger">{{ number_format($t['total_insurance'], 0, ',', '.') }}đ</td>
                            <td class="text-end text-danger">{{ number_format($t['total_tax'], 0, ',', '.') }}đ</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const labels = @json(array_column($trend, 'label'));
    const totalNet = @json(array_column($trend, 'total_net'));
    const totalWorking = @json(array_column($trend, 'total_working'));
    const totalOvertime = @json(array_column($trend, 'total_overtime'));
    const totalInsurance = @json(array_column($trend, 'total_insurance'));
    const totalTax = @json(array_column($trend, 'total_tax'));

    // Main trend chart
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                { label: 'Tổng lương thực nhận', data: totalNet, borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,0.1)', fill: true, tension: 0.3 },
                { label: 'Lương công', data: totalWorking, borderColor: '#198754', borderDash: [5,5], tension: 0.3 },
                { label: 'Tăng ca', data: totalOvertime, borderColor: '#ffc107', borderDash: [3,3], tension: 0.3 }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Overtime chart
    new Chart(document.getElementById('overtimeChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{ label: 'Tăng ca', data: totalOvertime, backgroundColor: '#ffc107', borderRadius: 4 }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    // Deduction chart
    new Chart(document.getElementById('deductionChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                { label: 'Bảo hiểm', data: totalInsurance, backgroundColor: '#dc3545', borderRadius: 4 },
                { label: 'Thuế TNCN', data: totalTax, backgroundColor: '#6f42c1', borderRadius: 4 }
            ]
        },
        options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
    });
});
</script>
@endpush
@endsection
