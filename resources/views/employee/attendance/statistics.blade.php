@extends('layouts.app')

@section('title', 'Thống Kê Chấm Công')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>
                <i class="fas fa-chart-line"></i> Thống Kê Chấm Công
            </h2>
        </div>
    </div>

    <!-- Month/Year Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tháng</label>
                    <select id="monthSelect" class="form-select">
                        <option value="1">Tháng 1</option>
                        <option value="2">Tháng 2</option>
                        <option value="3">Tháng 3</option>
                        <option value="4">Tháng 4</option>
                        <option value="5">Tháng 5</option>
                        <option value="6">Tháng 6</option>
                        <option value="7">Tháng 7</option>
                        <option value="8">Tháng 8</option>
                        <option value="9">Tháng 9</option>
                        <option value="10">Tháng 10</option>
                        <option value="11">Tháng 11</option>
                        <option value="12">Tháng 12</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Năm</label>
                    <input type="number" id="yearSelect" class="form-control" min="2020" max="2099" />
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button class="btn btn-primary" id="filterBtn">
                        <i class="fas fa-search"></i> Cập Nhật
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row" id="statsContainer">
        <div class="col-md-12 text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Đang tải...</span>
            </div>
        </div>
    </div>

    <!-- Detailed Chart and Table Section -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Biểu Đồ Giờ Làm</h5>
                </div>
                <div class="card-body">
                    <canvas id="workHoursChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Phân Loại Ngày Làm</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed List -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Chi Tiết Theo Ngày</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Ngày</th>
                        <th>Giờ Vào</th>
                        <th>Giờ Ra</th>
                        <th>Giờ Làm</th>
                        <th>Đi Muộn</th>
                        <th>Tiền Phạt</th>
                        <th>Về Sớm</th>
                        <th>Tăng Ca</th>
                        <th>Trạng Thái</th>
                    </tr>
                </thead>
                <tbody id="detailsTable">
                    <tr>
                        <td colspan="9" class="text-center py-4">Đang tải...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
const API_BASE = '/api/employee/attendance';
let workHoursChart = null;
let statusChart = null;

document.addEventListener('DOMContentLoaded', function() {
    const today = new Date();
    document.getElementById('monthSelect').value = today.getMonth() + 1;
    document.getElementById('yearSelect').value = today.getFullYear();

    loadStatistics();
    document.getElementById('filterBtn').addEventListener('click', loadStatistics);
});

async function loadStatistics() {
    const month = document.getElementById('monthSelect').value;
    const year = document.getElementById('yearSelect').value;

    try {
        const response = await fetch(`${API_BASE}/monthly-statistics?month=${month}&year=${year}`);
        const data = await response.json();

        if (data.success) {
            renderStatisticsCards(data.statistics);
            renderDetailedTable(data.details);
            renderCharts(data.details);
        }
    } catch (error) {
        console.error('Error loading statistics:', error);
        alert('Lỗi khi tải dữ liệu');
    }
}

function renderStatisticsCards(stats) {
    const html = `
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Ngày Làm</h6>
                    <h2 class="text-success">${stats.total_days_worked}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Ngày Vắng</h6>
                    <h2 class="text-danger">${stats.total_days_absent}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Lần Đi Muộn</h6>
                    <h2 class="text-warning">${stats.total_late_days}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Lần Về Sớm</h6>
                    <h2 class="text-info">${stats.total_early_leave_days}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mt-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Tổng Giờ Làm</h6>
                    <h2 class="text-primary">${stats.total_work_hours.toFixed(2)}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mt-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Tổng Phút Muộn</h6>
                    <h2>${stats.total_late_minutes}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mt-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Tổng Tiền Phạt</h6>
                    <h2 class="text-danger">${formatCurrency(stats.total_late_penalty_fee)}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mt-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Giờ Tăng Ca</h6>
                    <h2 class="text-success">${stats.total_overtime_hours.toFixed(2)}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mt-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Bình Quân/Ngày</h6>
                    <h2 class="text-primary">${stats.average_work_hours_per_day.toFixed(2)}</h2>
                </div>
            </div>
        </div>
    `;

    document.getElementById('statsContainer').innerHTML = html;
}

function renderDetailedTable(details) {
    const tbody = document.getElementById('detailsTable');

    if (details.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">Không có dữ liệu</td></tr>';
        return;
    }

    tbody.innerHTML = details.map(item => `
        <tr>
            <td><strong>${item.date}</strong></td>
            <td>${item.check_in || '-'}</td>
            <td>${item.check_out || '-'}</td>
            <td>${item.work_hours > 0 ? item.work_hours.toFixed(2) : '-'}</td>
            <td>${item.late_minutes > 0 ? item.late_minutes + ' phút' : '-'}</td>
            <td>${item.late_penalty_fee > 0 ? '<span class="text-danger">' + formatCurrency(item.late_penalty_fee) + '</span>' : '-'}</td>
            <td>${item.early_leave_minutes > 0 ? item.early_leave_minutes + ' phút' : '-'}</td>
            <td>${item.overtime_hours > 0 ? item.overtime_hours.toFixed(2) + ' giờ' : '-'}</td>
            <td>${getStatusBadge(item.status)}</td>
        </tr>
    `).join('');
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount || 0) + ' ₫';
}

function renderCharts(details) {
    // Prepare data for charts
    const dates = details.map(d => d.date);
    const workHours = details.map(d => d.work_hours);
    const statuses = countStatuses(details);

    // Work Hours Chart
    const workHoursCtx = document.getElementById('workHoursChart').getContext('2d');
    if (workHoursChart) workHoursChart.destroy();
    
    workHoursChart = new Chart(workHoursCtx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Giờ Làm',
                data: workHours,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.3,
                fill: true,
            }],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: true },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 10,
                },
            },
        },
    });

    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    if (statusChart) statusChart.destroy();
    
    statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Đúng Giờ', 'Đi Muộn', 'Về Sớm', 'Tăng Ca', 'Vắng'],
            datasets: [{
                data: [
                    statuses.present || 0,
                    statuses.late || 0,
                    statuses.leave_early || 0,
                    statuses.overtime || 0,
                    statuses.absent || 0,
                ],
                backgroundColor: [
                    '#28a745',
                    '#ffc107',
                    '#17a2b8',
                    '#007bff',
                    '#6c757d',
                ],
            }],
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                },
            },
        },
    });
}

function countStatuses(details) {
    return details.reduce((acc, item) => {
        const status = item.status || 'absent';
        acc[status] = (acc[status] || 0) + 1;
        return acc;
    }, {});
}

function getStatusBadge(status) {
    const statuses = {
        'present': '<span class="badge bg-success">Đúng Giờ</span>',
        'late': '<span class="badge bg-warning">Đi Muộn</span>',
        'leave_early': '<span class="badge bg-info">Về Sớm</span>',
        'late_and_leave_early': '<span class="badge bg-danger">Muộn & Sớm</span>',
        'overtime': '<span class="badge bg-primary">Tăng Ca</span>',
        'absent': '<span class="badge bg-secondary">Vắng</span>',
    };
    return statuses[status] || `<span class="badge bg-dark">${status}</span>`;
}
</script>
@endsection
