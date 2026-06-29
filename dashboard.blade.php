@extends('layouts.app')

@section('title', 'Chấm Công - Dashboard')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-8">
            <!-- Today's Attendance Card -->
            <div class="card mb-4 shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-day"></i> Chấm Công Hôm Nay - {{ now()->format('d/m/Y (l)') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div id="todayAttendanceContainer">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Đang tải...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Check-in/Check-out Section -->
            <div class="card mb-4 shadow-lg">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-qrcode"></i> Chấm Công Vào/Ra
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <button class="btn btn-success btn-lg w-100" id="checkInBtn" disabled>
                                <i class="fas fa-arrow-right"></i> Chấm Công Vào
                            </button>
                        </div>
                        <div class="col-md-6 mb-3">
                            <button class="btn btn-danger btn-lg w-100" id="checkOutBtn" disabled>
                                <i class="fas fa-arrow-left"></i> Chấm Công Ra
                            </button>
                        </div>
                    </div>
                    <div id="mapContainer" class="mb-3" style="height: 300px; border-radius: 8px; overflow: hidden;">
                        <div id="map" style="width: 100%; height: 100%;"></div>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Ghi Chú (Tùy Chọn)</label>
                        <textarea class="form-control" id="notes" placeholder="Nhập ghi chú..." rows="2"></textarea>
                    </div>
                    <div id="statusMessage" class="alert" style="display: none;"></div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card shadow">
                        <div class="card-body text-center">
                            <h6 class="text-muted">Giờ Làm Hôm Nay</h6>
                            <h3 class="text-primary" id="todayWorkHours">--</h3>
                            <small class="text-muted">giờ</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow">
                        <div class="card-body text-center">
                            <h6 class="text-muted">Trạng Thái Hôm Nay</h6>
                            <h3 id="todayStatus">--</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Monthly Summary Card -->
            <div class="card shadow-lg mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar"></i> Thống Kê Tháng Này
                    </h5>
                </div>
                <div class="card-body">
                    <div id="monthlySummaryContainer">
                        <div class="spinner-border spinner-border-sm text-info" role="status">
                            <span class="visually-hidden">Đang tải...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-link"></i> Liên Kết Nhanh
                    </h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('employee.attendance.history') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-history"></i> Xem Lịch Sử Chấm Công
                    </a>
                    <a href="{{ route('employee.attendance.statistics') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-line"></i> Xem Thống Kê Chi Tiết
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet Map Library -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.css" />
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.js"></script>

<script>
const API_BASE = '/api/employee/attendance';
let map = null;
let userMarker = null;
let officeMarker = null;
let circleMarker = null;
let currentLocation = null;

document.addEventListener('DOMContentLoaded', function() {
    initMap();
    loadTodayAttendance();
    loadMonthlySummary();
    setupGeolocation();
});

function initMap() {
    map = L.map('map').setView([21.0285, 105.8542], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    // Office marker (green)
    officeMarker = L.circleMarker([21.0285, 105.8542], {
        radius: 8,
        fillColor: '#28a745',
        color: '#fff',
        weight: 2,
        opacity: 1,
        fillOpacity: 0.8,
    }).addTo(map);
    officeMarker.bindPopup('Văn Phòng');

    // Allowed distance circle
    circleMarker = L.circle([21.0285, 105.8542], {
        radius: 100,
        color: '#28a745',
        fill: false,
        weight: 2,
        dashArray: '5, 5',
        opacity: 0.5,
    }).addTo(map);
}

function setupGeolocation() {
    if ('geolocation' in navigator) {
        // Watch position continuously
        navigator.geolocation.watchPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                currentLocation = { latitude: lat, longitude: lng };

                // Update or create user marker (red)
                if (userMarker) {
                    userMarker.setLatLng([lat, lng]);
                } else {
                    userMarker = L.circleMarker([lat, lng], {
                        radius: 8,
                        fillColor: '#dc3545',
                        color: '#fff',
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.8,
                    }).addTo(map);
                    userMarker.bindPopup('Vị Trí Của Bạn');
                }

                // Center map on user
                map.setView([lat, lng], 15);

                // Enable buttons only if we have valid location
                document.getElementById('checkInBtn').disabled = false;
                document.getElementById('checkOutBtn').disabled = false;
            },
            function(error) {
                showMessage('Lỗi: ' + error.message, 'danger');
                console.error('Geolocation error:', error);
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0,
            }
        );
    } else {
        showMessage('Trình duyệt của bạn không hỗ trợ xác định vị trí.', 'danger');
    }
}

async function loadTodayAttendance() {
    try {
        const response = await fetch(`${API_BASE}/today`);
        const data = await response.json();

        if (data.success && data.attendance) {
            const att = data.attendance;
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Giờ Vào:</strong> ${att.check_in ? formatTime(att.check_in) : 'Chưa chấm'}</p>
                        <p><strong>Vị Trí:</strong> ${att.check_in_location || 'N/A'}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Giờ Ra:</strong> ${att.check_out ? formatTime(att.check_out) : 'Chưa chấm'}</p>
                        <p><strong>Vị Trí:</strong> ${att.check_out_location || 'N/A'}</p>
                    </div>
                </div>
            `;

            document.getElementById('todayAttendanceContainer').innerHTML = html;
            document.getElementById('todayWorkHours').textContent = (att.work_hours || 0).toFixed(2);
            document.getElementById('todayStatus').innerHTML = `<span class="badge bg-info">${getStatusBadge(att.status)}</span>`;
        } else {
            document.getElementById('todayAttendanceContainer').innerHTML = '<p class="text-muted">Chưa có dữ liệu chấm công hôm nay</p>';
        }
    } catch (error) {
        console.error('Error loading today attendance:', error);
    }
}

async function loadMonthlySummary() {
    try {
        const today = new Date();
        const month = today.getMonth() + 1;
        const year = today.getFullYear();

        const response = await fetch(`${API_BASE}/monthly-summary?month=${month}&year=${year}`);
        const data = await response.json();

        if (data.success) {
            const s = data.summary;
            const html = `
                <div class="stats-list">
                    <div class="stat-item mb-2">
                        <span class="stat-label">Ngày Làm:</span>
                        <span class="stat-value text-success">${s.worked_days}</span>
                    </div>
                    <div class="stat-item mb-2">
                        <span class="stat-label">Ngày Vắng:</span>
                        <span class="stat-value text-danger">${s.absent_days}</span>
                    </div>
                    <div class="stat-item mb-2">
                        <span class="stat-label">Lần Đi Muộn:</span>
                        <span class="stat-value text-warning">${s.late_days}</span>
                    </div>
                    <div class="stat-item mb-2">
                        <span class="stat-label">Lần Về Sớm:</span>
                        <span class="stat-value text-info">${s.early_leave_days}</span>
                    </div>
                    <hr>
                    <div class="stat-item mb-2">
                        <span class="stat-label">Tổng Giờ Làm:</span>
                        <span class="stat-value text-primary">${s.total_hours.toFixed(2)}</span>
                    </div>
                    <div class="stat-item mb-2">
                        <span class="stat-label">Tổng Phút Muộn:</span>
                        <span class="stat-value">${s.total_late_minutes}</span>
                    </div>
                    <div class="stat-item mb-2">
                        <span class="stat-label">Giờ Tăng Ca:</span>
                        <span class="stat-value text-success">${s.overtime_hours.toFixed(2)}</span>
                    </div>
                    <div class="stat-item mb-2">
                        <span class="stat-label">Bình Quân Ngày:</span>
                        <span class="stat-value">${s.average_daily_hours.toFixed(2)}</span>
                    </div>
                </div>
            `;
            document.getElementById('monthlySummaryContainer').innerHTML = html;
        }
    } catch (error) {
        console.error('Error loading monthly summary:', error);
    }
}

async function checkIn() {
    if (!currentLocation) {
        showMessage('Không thể lấy vị trí. Vui lòng thử lại.', 'warning');
        return;
    }

    const notes = document.getElementById('notes').value;

    try {
        const response = await fetch(`${API_BASE}/check-in`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                latitude: currentLocation.latitude,
                longitude: currentLocation.longitude,
                notes: notes,
            }),
        });

        const data = await response.json();

        if (data.success) {
            showMessage(data.message, 'success');
            document.getElementById('notes').value = '';
            setTimeout(loadTodayAttendance, 500);
        } else {
            showMessage(data.message, 'danger');
        }
    } catch (error) {
        showMessage('Lỗi: ' + error.message, 'danger');
    }
}

async function checkOut() {
    if (!currentLocation) {
        showMessage('Không thể lấy vị trí. Vui lòng thử lại.', 'warning');
        return;
    }

    const notes = document.getElementById('notes').value;

    try {
        const response = await fetch(`${API_BASE}/check-out`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                latitude: currentLocation.latitude,
                longitude: currentLocation.longitude,
                notes: notes,
            }),
        });

        const data = await response.json();

        if (data.success) {
            showMessage(data.message, 'success');
            document.getElementById('notes').value = '';
            setTimeout(loadTodayAttendance, 500);
        } else {
            showMessage(data.message, 'danger');
        }
    } catch (error) {
        showMessage('Lỗi: ' + error.message, 'danger');
    }
}

function showMessage(msg, type) {
    const container = document.getElementById('statusMessage');
    container.className = `alert alert-${type}`;
    container.textContent = msg;
    container.style.display = 'block';

    setTimeout(() => {
        container.style.display = 'none';
    }, 5000);
}

function formatTime(datetime) {
    if (!datetime) return '--';
    const date = new Date(datetime);
    return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

function getStatusBadge(status) {
    const statuses = {
        'present': 'Đúng Giờ',
        'late': 'Đi Muộn',
        'leave_early': 'Về Sớm',
        'late_and_leave_early': 'Muộn & Sớm',
        'overtime': 'Tăng Ca',
        'absent': 'Vắng',
    };
    return statuses[status] || status;
}

// Event listeners
document.getElementById('checkInBtn').addEventListener('click', checkIn);
document.getElementById('checkOutBtn').addEventListener('click', checkOut);
</script>

<style>
.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stat-label {
    font-weight: 500;
    color: #666;
}

.stat-value {
    font-weight: bold;
    font-size: 1.1em;
}
</style>
@endsection