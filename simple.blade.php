@extends('layouts.app')

@section('title', 'Chấm Công - Đơn Giản')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <!-- Card Chấm Công -->
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white py-4">
                    <h3 class="mb-0 text-center">
                        <i class="fas fa-clock"></i> CHẤM CÔNG
                    </h3>
                </div>

                <div class="card-body p-5">
                    <!-- Employee Info -->
                    <div class="mb-4">
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted">Họ và Tên</small>
                                <h5 id="employeeName">{{ $employee->name }}</h5>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Mã Nhân Viên</small>
                                <h5 id="employeeId">{{ $employee->id }}</h5>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Ngày</small>
                                <p class="mb-0"><strong>{{ $today->format('d/m/Y') }}</strong></p>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Giờ Hiện Tại</small>
                                <p class="mb-0"><strong id="currentTime">--:--:--</strong></p>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Attendance Status -->
                    <div class="mb-5">
                        <div class="text-center">
                            <small class="text-muted d-block mb-2">TRẠNG THÁI HÔM NAY</small>
                            <div id="statusContainer" class="mb-3">
                                <h6 class="text-muted">Chưa chấm công</h6>
                            </div>

                            <!-- Check-in Time -->
                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted">Giờ Vào</small>
                                    <p id="checkInTime" class="text-success font-weight-bold mb-0">--</p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Giờ Ra</small>
                                    <p id="checkOutTime" class="text-danger font-weight-bold mb-0">--</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Attendance Button -->
                    <div class="text-center mb-4">
                        <button class="btn btn-success btn-lg btn-block py-3" id="attendanceBtn" style="font-size: 1.2rem; border-radius: 50px;">
                            <i class="fas fa-check-circle"></i> CHẤM CÔNG
                        </button>
                    </div>

                    <!-- Message -->
                    <div id="message" class="alert" style="display: none;" role="alert"></div>

                    <!-- Statistics (after check-out) -->
                    <div id="statsContainer" style="display: none;">
                        <hr>
                        <div class="alert alert-info">
                            <h6 class="mb-2"><i class="fas fa-chart-bar"></i> Thống Kê Hôm Nay</h6>
                            <small>
                                <p class="mb-1"><strong>Giờ Làm:</strong> <span id="workHours">0</span> giờ</p>
                                <p class="mb-1"><strong>Đi Muộn:</strong> <span id="lateMinutes">0</span> phút</p>
                                <p class="mb-1"><strong>Về Sớm:</strong> <span id="earlyLeaveMinutes">0</span> phút</p>
                                <p class="mb-0"><strong>Tăng Ca:</strong> <span id="overtimeHours">0</span> giờ</p>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="card-footer bg-light text-center text-muted py-3">
                    <small>
                        <i class="fas fa-info-circle"></i> Hệ thống sẽ tự động xác định Chấm Công Vào hoặc Chấm Công Ra
                    </small>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="mt-4 text-center">
                <a href="{{ route('employee.attendance.history') }}" class="btn btn-outline-primary btn-sm me-2">
                    <i class="fas fa-history"></i> Lịch Sử
                </a>
                <a href="{{ route('employee.attendance.statistics') }}" class="btn btn-outline-info btn-sm">
                    <i class="fas fa-chart-line"></i> Thống Kê
                </a>
            </div>
        </div>
    </div>
</div>

<script>
const API_ATTENDANCE = '/api/employee/attendance/simple';
let attendanceStatus = 'not_checked';

// Update current time
function updateTime() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('currentTime').textContent = `${hours}:${minutes}:${seconds}`;
}

// Load today's attendance status
async function loadStatus() {
    try {
        const response = await fetch(`${API_ATTENDANCE}/today-status`);
        const data = await response.json();

        if (data.success) {
            attendanceStatus = data.status;
            updateUI(data);
        }
    } catch (error) {
        console.error('Error loading status:', error);
    }
}

// Update UI based on status
function updateUI(data) {
    const checkInEl = document.getElementById('checkInTime');
    const checkOutEl = document.getElementById('checkOutTime');
    const statusEl = document.getElementById('statusContainer');
    const statsEl = document.getElementById('statsContainer');
    const btn = document.getElementById('attendanceBtn');

    if (data.check_in) {
        checkInEl.textContent = data.check_in;
    }

    if (data.check_out) {
        checkOutEl.textContent = data.check_out;
        statsEl.style.display = 'block';

        if (data.attendance) {
            document.getElementById('workHours').textContent = (data.attendance.work_hours || 0).toFixed(2);
            document.getElementById('lateMinutes').textContent = data.attendance.late_minutes || 0;
            document.getElementById('earlyLeaveMinutes').textContent = data.attendance.early_leave_minutes || 0;
            document.getElementById('overtimeHours').textContent = (data.attendance.overtime_hours || 0).toFixed(2);
        }
    }

    // Update status and button
    if (data.status === 'completed') {
        statusEl.innerHTML = '<span class="badge bg-success" style="font-size: 1rem;">Đã Hoàn Thành</span>';
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-check"></i> Đã Chấm Công';
    } else if (data.status === 'working') {
        statusEl.innerHTML = '<span class="badge bg-warning text-dark" style="font-size: 1rem;">Đang Làm Việc</span>';
        btn.innerHTML = '<i class="fas fa-arrow-left"></i> Chấm Công Ra';
    } else {
        statusEl.innerHTML = '<span class="badge bg-secondary" style="font-size: 1rem;">Chưa Chấm Công</span>';
        btn.innerHTML = '<i class="fas fa-arrow-right"></i> Chấm Công Vào';
    }
}

// Handle attendance button click
async function handleAttendanceClick() {
    const btn = document.getElementById('attendanceBtn');
    const msgEl = document.getElementById('message');

    if (btn.disabled) return;

    btn.disabled = true;
    msgEl.style.display = 'none';

    try {
        const response = await fetch(`${API_ATTENDANCE}/check`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        const data = await response.json();

        if (data.success) {
            msgEl.className = 'alert alert-success';
            msgEl.textContent = data.message;
            msgEl.style.display = 'block';

            // Reload status after 1 second
            setTimeout(loadStatus, 1000);
        } else {
            msgEl.className = 'alert alert-danger';
            msgEl.textContent = data.message || 'Lỗi không xác định';
            msgEl.style.display = 'block';
            btn.disabled = false;
        }
    } catch (error) {
        msgEl.className = 'alert alert-danger';
        msgEl.textContent = 'Lỗi: ' + error.message;
        msgEl.style.display = 'block';
        btn.disabled = false;
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    updateTime();
    setInterval(updateTime, 1000);

    loadStatus();

    document.getElementById('attendanceBtn').addEventListener('click', handleAttendanceClick);
});
</script>

<style>
.btn-block {
    width: 100%;
}

#attendanceBtn:disabled {
    cursor: not-allowed;
    opacity: 0.7;
}

.card {
    border-radius: 15px;
}

.card-header {
    border-radius: 15px 15px 0 0;
}
</style>
@endsection