@extends('layouts.app')

@section('title', 'Lịch Sử Chấm Công')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('me.dashboard') }}">Dashboard</a></li>
<li>Lịch sử chấm công</li>
@endsection
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>
                <i class="fas fa-history"></i> Lịch Sử Chấm Công
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
                        <i class="fas fa-search"></i> Tìm Kiếm
                    </button>
                    <button class="btn btn-info ms-2" id="exportBtn">
                        <i class="fas fa-download"></i> Xuất Excel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Danh Sách Chấm Công</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Ngày</th>
                        <th>Thứ</th>
                        <th>Giờ Vào</th>
                        <th>Giờ Ra</th>
                        <th>Giờ Làm</th>
                        <th>Đi Muộn (phút)</th>
                        <th>Về Sớm (phút)</th>
                        <th>Tăng Ca (giờ)</th>
                        <th>Trạng Thái</th>
                        <th>Ghi Chú</th>
                    </tr>
                </thead>
                <tbody id="attendanceTable">
                    <tr>
                        <td colspan="10" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Đang tải...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date();
    document.getElementById('monthSelect').value = today.getMonth() + 1;
    document.getElementById('yearSelect').value = today.getFullYear();

    loadAttendanceHistory();

    document.getElementById('filterBtn').addEventListener('click', loadAttendanceHistory);
    document.getElementById('exportBtn').addEventListener('click', exportToExcel);
});

async function loadAttendanceHistory() {
    const month = document.getElementById('monthSelect').value;
    const year = document.getElementById('yearSelect').value;

    try {
        const response = await fetch(`/api/employee/attendance/history?month=${month}&year=${year}`);
        const data = await response.json();

        if (data.success && data.attendances) {
            renderTable(data.attendances);
        }
    } catch (error) {
        console.error('Error loading attendance history:', error);
        alert('Lỗi khi tải dữ liệu');
    }
}

function renderTable(attendances) {
    const tbody = document.getElementById('attendanceTable');

    if (attendances.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center py-4 text-muted">Không có dữ liệu</td></tr>';
        return;
    }

    tbody.innerHTML = attendances.map(att => `
        <tr>
            <td><strong>${formatDate(att.date)}</strong></td>
            <td>${getDayOfWeek(att.date)}</td>
            <td>${att.check_in ? formatTime(att.check_in) : '-'}</td>
            <td>${att.check_out ? formatTime(att.check_out) : '-'}</td>
            <td>${att.work_hours ? att.work_hours.toFixed(2) : '-'}</td>
            <td>${att.late_minutes || 0}</td>
            <td>${att.early_leave_minutes || 0}</td>
            <td>${att.overtime_hours ? att.overtime_hours.toFixed(2) : 0}</td>
            <td>${getStatusBadge(att.status)}</td>
            <td>${att.notes || '-'}</td>
        </tr>
    `).join('');
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('vi-VN');
}

function formatTime(timeStr) {
    if (!timeStr) return '-';
    // Handle both datetime and time formats
    const time = timeStr.split('T')[1] || timeStr;
    return time.substring(0, 5);
}

function getDayOfWeek(dateStr) {
    const days = ['CN', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
    const date = new Date(dateStr);
    return days[date.getDay()];
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

function exportToExcel() {
    alert('Chức năng này sẽ được triển khai trong phiên bản tiếp theo');
    // TODO: Implement Excel export
}
</script>
@endsection
