@extends('layouts.employee')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('me.dashboard') }}">Dashboard</a></li>
<li>Chấm công</li>
@endsection
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- CSRF Token Meta -->
        <meta name="csrf-token" content="{{ csrf_token() }}">
        
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Chấm Công</h1>
            <p class="text-gray-600">Hôm nay: <strong>{{ date('d/m/Y H:i:s') }}</strong></p>
        </div>

        <!-- Map Section -->
        <div class="bg-white rounded-lg shadow-md mb-6 overflow-hidden">
            <div id="map" class="w-full h-96"></div>
        </div>

        <!-- Status & Information Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <!-- Check-in Status -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-sm text-gray-600 mb-2">Chấm Công Vào</div>
                <div id="check-in-time" class="text-2xl font-bold text-gray-800">--:--:--</div>
                <div id="check-in-status" class="text-sm text-gray-500 mt-2">Chưa chấm công</div>
            </div>

            <!-- Current Location -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-sm text-gray-600 mb-2">Vị Trí Hiện Tại</div>
                <div id="current-distance" class="text-2xl font-bold text-gray-800">--/100m</div>
                <div id="current-location" class="text-sm text-gray-500 mt-2">Đang xác định...</div>
            </div>

            <!-- Check-out Status -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-sm text-gray-600 mb-2">Chấm Công Ra</div>
                <div id="check-out-time" class="text-2xl font-bold text-gray-800">--:--:--</div>
                <div id="check-out-status" class="text-sm text-gray-500 mt-2">Chưa chấm công</div>
            </div>
        </div>

        <!-- Check-in/Check-out Buttons -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Check-in Section -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Chấm Công Vào</h3>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ghi Chú (Tùy Chọn)</label>
                        <textarea id="check-in-notes" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500" rows="3" placeholder="Nhập ghi chú nếu cần..."></textarea>
                    </div>
                    <button id="check-in-btn" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200" onclick="handleCheckIn()">
                        <span class="inline-block mr-2">📍</span> Chấm Công Vào
                    </button>
                    <div id="check-in-message" class="mt-3 text-sm"></div>
                </div>

                <!-- Check-out Section -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Chấm Công Ra</h3>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ghi Chú (Tùy Chọn)</label>
                        <textarea id="check-out-notes" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500" rows="3" placeholder="Nhập ghi chú nếu cần..."></textarea>
                    </div>
                    <button id="check-out-btn" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed" onclick="handleCheckOut()" disabled>
                        <span class="inline-block mr-2">📍</span> Chấm Công Ra
                    </button>
                    <div id="check-out-message" class="mt-3 text-sm"></div>
                </div>
            </div>
        </div>

        <!-- Face Attendance Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">📸 Chấm Công Bằng Khuôn Mặt</h3>
                        <p class="text-sm text-gray-600">Sử dụng camera để xác thực khuôn mặt và chấm công.</p>
                    </div>
                    <span id="face-registration-status" class="text-sm font-semibold text-blue-600">Đang kiểm tra đăng ký...</span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div>
                        <button id="open-face-camera-btn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200" onclick="initializeFaceCamera()">
                            📷 Mở Camera
                        </button>
                    </div>
                    <div>
                        <button id="capture-face-btn" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed" onclick="captureFaceImage()" disabled>
                            🖼️ Chụp Ảnh
                        </button>
                    </div>
                    <div>
                        <button id="register-face-btn" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed" onclick="registerFace()" disabled>
                            ✅ Đăng Ký Khuôn Mặt
                        </button>
                    </div>
                    <div>
                        <button id="face-attendance-btn" class="w-full bg-violet-600 hover:bg-violet-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed" onclick="submitFaceAttendance()" disabled>
                            📸 Chấm Công Bằng Face
                        </button>
                    </div>
                </div>

                <div id="face-preview-panel" class="grid grid-cols-1 lg:grid-cols-2 gap-4 hidden">
                    <div class="rounded-lg border border-gray-200 overflow-hidden">
                        <div class="bg-gray-50 p-3 text-sm font-semibold text-gray-700">Camera Preview</div>
                        <video id="face-video" class="w-full h-72 bg-black" autoplay muted playsinline></video>
                    </div>
                    <div class="rounded-lg border border-gray-200 overflow-hidden">
                        <div class="bg-gray-50 p-3 text-sm font-semibold text-gray-700">Ảnh Chụp</div>
                        <div class="flex items-center justify-center p-4">
                            <img id="face-preview" class="max-h-72 rounded-md" src="" alt="Ảnh khuôn mặt" />
                        </div>
                    </div>
                </div>

                <div id="face-status-message" class="text-sm text-gray-700"></div>
                <canvas id="face-canvas" class="hidden"></canvas>
            </div>
        </div>

        <!-- Distance Alert -->
        <div id="distance-alert" class="mb-6 hidden">
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <span class="text-2xl">⚠️</span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            <strong>Thông báo:</strong> <span id="distance-alert-message">Bạn đang cách văn phòng quá xa</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Manual Location / Fallback Controls -->
        <div id="location-fallback" class="mb-6 hidden">
            <div class="bg-gray-50 border-l-4 border-gray-300 p-4 rounded">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm text-gray-700">Không thể truy cập vị trí của bạn.</p>
                        <p class="text-sm text-gray-600">Bạn có thể bật vị trí trong trình duyệt hoặc nhập tọa độ thủ công / chấm công không cần vị trí.</p>
                    </div>
                    <div class="flex gap-2">
                        <button id="manual-loc-btn" class="btn" onclick="showManualLocationForm()">Nhập tọa độ</button>
                        <button id="skip-loc-btn" class="btn" onclick="allowWithoutLocation()">Chấm công không cần vị trí</button>
                    </div>
                </div>
            </div>

            <div id="manual-loc-form" class="mt-3 hidden">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <input id="manual-lat" type="text" placeholder="Latitude" class="px-3 py-2 border rounded" />
                    <input id="manual-lon" type="text" placeholder="Longitude" class="px-3 py-2 border rounded" />
                    <div class="flex gap-2">
                        <button class="btn primary" onclick="submitManualCheckIn()">Chấm Vào (Tọa độ)</button>
                        <button class="btn" onclick="submitManualCheckOut()">Chấm Ra (Tọa độ)</button>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-2">Lưu ý: tọa độ sai có thể gây từ chối chấm công.</p>
            </div>
        </div>

        <!-- Attendance History -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Lịch Sử Chấm Công</h2>
            <div id="attendance-history" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2 text-left">Ngày</th>
                            <th class="px-4 py-2 text-left">Chấm Vào</th>
                            <th class="px-4 py-2 text-left">Chấm Ra</th>
                            <th class="px-4 py-2 text-left">Trạng Thái</th>
                            <th class="px-4 py-2 text-left">Vị Trí Vào</th>
                        </tr>
                    </thead>
                    <tbody id="history-table">
                        <tr>
                            <td class="px-4 py-2 text-center" colspan="5">Đang tải...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet Map Library (local copy) -->
<link rel="stylesheet" href="/vendor/leaflet/leaflet.css" />
<script src="/vendor/leaflet/leaflet.js"></script>

<script>
    // Global variables
    let map;
    let userMarker;
    let officeMarker;
    let currentLatitude;
    let currentLongitude;
    let officeLatitude;
    let officeLongitude;
    let allowedDistance;
    let todayAttendance = null;
    let locationWatchId = null;
    let locationAvailable = false;

    function setCheckButtonsEnabled(enabled) {
        const checkInBtn = document.getElementById('check-in-btn');
        const checkOutBtn = document.getElementById('check-out-btn');

        checkInBtn.disabled = !enabled;
        checkOutBtn.disabled = !enabled || !!todayAttendance?.check_out;

        if (enabled) {
            checkInBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            checkOutBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            checkInBtn.classList.add('opacity-50', 'cursor-not-allowed');
            checkOutBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        initializeMap();
        loadOfficeLocation();
        loadTodayAttendance();
        startLocationTracking();
        loadAttendanceHistory();
        loadFaceProfile();
    });

    // Initialize Map
    function initializeMap() {
        // Default to Hanoi
        map = L.map('map').setView([21.0285, 105.8542], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(map);
    }

    // Load office location from API
    function loadOfficeLocation() {
        console.log('Loading office location from /api/employee/attendance/office-location');
        fetch('/api/employee/attendance/office-location', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                officeLatitude = data.office_latitude;
                officeLongitude = data.office_longitude;
                allowedDistance = data.allowed_distance;

                // Add office marker
                officeMarker = L.marker([officeLatitude, officeLongitude], {
                    icon: L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41]
                    })
                }).addTo(map)
                .bindPopup('Văn Phòng Công Ty');

                // Draw circle for allowed distance
                L.circle([officeLatitude, officeLongitude], {
                    color: 'blue',
                    fillColor: '#30b0eb',
                    fillOpacity: 0.2,
                    radius: allowedDistance
                }).addTo(map);

                map.setView([officeLatitude, officeLongitude], 15);
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Load today's attendance
    function loadTodayAttendance() {
        fetch('/api/employee/attendance/today', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.attendance) {
                todayAttendance = data.attendance;
                updateAttendanceDisplay();
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Update attendance display
    function updateAttendanceDisplay() {
        if (todayAttendance) {
            if (todayAttendance.check_in) {
                const checkInTime = new Date(todayAttendance.check_in);
                document.getElementById('check-in-time').textContent = checkInTime.toLocaleTimeString('vi-VN');
                document.getElementById('check-in-status').textContent = `Khoảng cách: ${todayAttendance.check_in_distance}m`;
                document.getElementById('check-in-btn').disabled = true;
                document.getElementById('check-in-btn').classList.add('opacity-50', 'cursor-not-allowed');
                document.getElementById('check-out-btn').disabled = false;
                document.getElementById('check-out-btn').classList.remove('opacity-50', 'cursor-not-allowed');
            }

            if (todayAttendance.check_out) {
                const checkOutTime = new Date(todayAttendance.check_out);
                document.getElementById('check-out-time').textContent = checkOutTime.toLocaleTimeString('vi-VN');
                document.getElementById('check-out-status').textContent = `Khoảng cách: ${todayAttendance.check_out_distance}m`;
                document.getElementById('check-out-btn').disabled = true;
                document.getElementById('check-out-btn').classList.add('opacity-50', 'cursor-not-allowed');
            }

            // Update status display
            const statusColors = {
                'present': 'text-green-600',
                'late': 'text-yellow-600',
                'absent': 'text-red-600',
                'leave': 'text-blue-600'
            };
            const statusTexts = {
                'present': 'Đúng Giờ',
                'late': 'Trễ',
                'absent': 'Vắng',
                'leave': 'Nghỉ'
            };
        }
    }

    // Start location tracking
    function startLocationTracking() {
        if (!navigator.geolocation) {
            showMessage('check-in-message', 'Trình duyệt không hỗ trợ Geolocation', 'error');
            useTestLocation();
            setCheckButtonsEnabled(false);
            return;
        }

        // If Permissions API available, check geolocation permission state first
        if (navigator.permissions && navigator.permissions.query) {
            navigator.permissions.query({ name: 'geolocation' }).then(function (permissionStatus) {
                if (permissionStatus.state === 'denied') {
                    // User has previously denied; avoid prompting and show fallback
                    const err = { code: 1, message: 'User denied Geolocation' };
                    handleLocationError(err);
                    useTestLocation();
                    return;
                }

                // Otherwise request current position and watch
                navigator.geolocation.getCurrentPosition(
                    position => updateMapWithUserLocation(position, false),
                    error => {
                        handleLocationError(error);
                        useTestLocation();
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );

                locationWatchId = navigator.geolocation.watchPosition(
                    position => updateMapWithUserLocation(position, false),
                    error => {
                        console.error('Location watch error:', error);
                        if (!locationAvailable) {
                            handleLocationError(error);
                        }
                        if (error.code === error.PERMISSION_DENIED && locationWatchId !== null) {
                            navigator.geolocation.clearWatch(locationWatchId);
                            locationWatchId = null;
                        }
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            }).catch(function () {
                // If permissions query fails, fall back to normal behaviour
                navigator.geolocation.getCurrentPosition(
                    position => updateMapWithUserLocation(position, false),
                    error => {
                        handleLocationError(error);
                        useTestLocation();
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );

                locationWatchId = navigator.geolocation.watchPosition(
                    position => updateMapWithUserLocation(position, false),
                    error => {
                        console.error('Location watch error:', error);
                        if (!locationAvailable) {
                            handleLocationError(error);
                        }
                        if (error.code === error.PERMISSION_DENIED && locationWatchId !== null) {
                            navigator.geolocation.clearWatch(locationWatchId);
                            locationWatchId = null;
                        }
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            });
        } else {
            // No Permissions API: proceed as before
            navigator.geolocation.getCurrentPosition(
                position => updateMapWithUserLocation(position, false),
                error => {
                    handleLocationError(error);
                    useTestLocation();
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );

            locationWatchId = navigator.geolocation.watchPosition(
                position => updateMapWithUserLocation(position, false),
                error => {
                    console.error('Location watch error:', error);
                    if (!locationAvailable) {
                        handleLocationError(error);
                    }
                    if (error.code === error.PERMISSION_DENIED && locationWatchId !== null) {
                        navigator.geolocation.clearWatch(locationWatchId);
                        locationWatchId = null;
                    }
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }
    }

    // Use test location for development
    function useTestLocation() {
        const testPosition = {
            coords: {
                latitude: 21.0285,
                longitude: 105.8542,
                accuracy: 100
            }
        };
        updateMapWithUserLocation(testPosition, true);
        console.log('Using test location for development');
    }

    // Update map with user location
    function updateMapWithUserLocation(position, isFake = false) {
        currentLatitude = position.coords.latitude;
        currentLongitude = position.coords.longitude;
        const accuracy = position.coords.accuracy;
        locationAvailable = !isFake;

        // Update or create user marker
        if (userMarker) {
            userMarker.setLatLng([currentLatitude, currentLongitude]);
        } else {
            userMarker = L.marker([currentLatitude, currentLongitude], {
                icon: L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34],
                    shadowSize: [41, 41]
                })
            }).addTo(map)
            .bindPopup('Vị Trí Của Tôi');
        }

        // Center map on user
        map.setView([currentLatitude, currentLongitude], 15);

        // Calculate and display distance
        if (officeLatitude && officeLongitude) {
            const distance = calculateDistance(currentLatitude, currentLongitude, officeLatitude, officeLongitude);
            const distancePercent = Math.round((distance / allowedDistance) * 100);
            
            document.getElementById('current-distance').textContent = `${Math.round(distance)}/${allowedDistance}m`;
            document.getElementById('current-location').textContent = `${currentLatitude.toFixed(6)}, ${currentLongitude.toFixed(6)}`;

            // Show warning if too far
            if (distance > allowedDistance) {
                document.getElementById('distance-alert').classList.remove('hidden');
                document.getElementById('distance-alert-message').textContent = `Bạn đang cách văn phòng ${Math.round(distance)}m. Không thể chấm công ngoài phạm vi ${allowedDistance}m.`;
            } else {
                document.getElementById('distance-alert').classList.add('hidden');
            }
        }
    }

    // Calculate distance using Haversine formula
    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371000; // meters
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c; // distance in meters
    }

    // Handle check-in
    function handleCheckIn() {
        // If no GPS but user allowed skip or provided manual coords, allow submission with null coords
        if ((!currentLatitude || !currentLongitude) && !document.getElementById('manual-loc-form')?.classList.contains('hidden')) {
            // attempt to use manual fields if provided
            const latVal = parseFloat(document.getElementById('manual-lat').value);
            const lonVal = parseFloat(document.getElementById('manual-lon').value);
            if (!isNaN(latVal) && !isNaN(lonVal)) {
                currentLatitude = latVal; currentLongitude = lonVal;
            }
        }

        if (!currentLatitude || !currentLongitude) {
            // allow submit with null coords if buttons are enabled (user opted to skip)
            if (document.getElementById('check-in-btn').disabled) {
                showMessage('check-in-message', 'Không thể xác định vị trí. Vui lòng cho phép truy cập vị trí.', 'error');
                return;
            }
        }

        const notes = document.getElementById('check-in-notes').value;
        
        fetch('/api/employee/attendance/check-in', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            credentials: 'include',
            body: JSON.stringify({
                latitude: currentLatitude || null,
                longitude: currentLongitude || null,
                notes: notes
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('check-in-message', data.message, 'success');
                document.getElementById('check-in-notes').value = '';
                loadTodayAttendance();
                setTimeout(() => loadAttendanceHistory(), 500);
            } else {
                showMessage('check-in-message', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('check-in-message', 'Có lỗi xảy ra. Vui lòng thử lại.', 'error');
        });
    }

    // Handle check-out
    function handleCheckOut() {
        if ((!currentLatitude || !currentLongitude) && !document.getElementById('manual-loc-form')?.classList.contains('hidden')) {
            const latVal = parseFloat(document.getElementById('manual-lat').value);
            const lonVal = parseFloat(document.getElementById('manual-lon').value);
            if (!isNaN(latVal) && !isNaN(lonVal)) {
                currentLatitude = latVal; currentLongitude = lonVal;
            }
        }

        if (!currentLatitude || !currentLongitude) {
            if (document.getElementById('check-out-btn').disabled) {
                showMessage('check-out-message', 'Không thể xác định vị trí. Vui lòng cho phép truy cập vị trí.', 'error');
                return;
            }
        }

        const notes = document.getElementById('check-out-notes').value;
        
        fetch('/api/employee/attendance/check-out', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            credentials: 'include',
            body: JSON.stringify({
                latitude: currentLatitude || null,
                longitude: currentLongitude || null,
                notes: notes
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('check-out-message', data.message, 'success');
                document.getElementById('check-out-notes').value = '';
                loadTodayAttendance();
                setTimeout(() => loadAttendanceHistory(), 500);
            } else {
                showMessage('check-out-message', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('check-out-message', 'Có lỗi xảy ra. Vui lòng thử lại.', 'error');
        });
    }

    // Load attendance history
    function loadAttendanceHistory() {
        const month = new Date().getMonth() + 1;
        const year = new Date().getFullYear();

        fetch(`/api/employee/attendance/history?month=${month}&year=${year}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayAttendanceHistory(data.attendances);
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Display attendance history
    function displayAttendanceHistory(attendances) {
        const table = document.getElementById('history-table');
        table.innerHTML = '';

        if (attendances.length === 0) {
            table.innerHTML = '<tr><td class="px-4 py-2 text-center" colspan="5">Không có dữ liệu</td></tr>';
            return;
        }

        attendances.forEach(attendance => {
            const checkInTime = attendance.check_in ? new Date(attendance.check_in).toLocaleTimeString('vi-VN') : '--:--:--';
            const checkOutTime = attendance.check_out ? new Date(attendance.check_out).toLocaleTimeString('vi-VN') : '--:--:--';
            
            const statusColor = {
                'present': 'bg-green-100 text-green-800',
                'late': 'bg-yellow-100 text-yellow-800',
                'absent': 'bg-red-100 text-red-800',
                'leave': 'bg-blue-100 text-blue-800'
            }[attendance.status] || 'bg-gray-100 text-gray-800';

            const statusText = {
                'present': 'Đúng Giờ',
                'late': 'Trễ',
                'absent': 'Vắng',
                'leave': 'Nghỉ'
            }[attendance.status] || attendance.status;

            const row = document.createElement('tr');
            row.className = 'border-t border-gray-200 hover:bg-gray-50';
            row.innerHTML = `
                <td class="px-4 py-2">${new Date(attendance.date).toLocaleDateString('vi-VN')}</td>
                <td class="px-4 py-2">${checkInTime}</td>
                <td class="px-4 py-2">${checkOutTime}</td>
                <td class="px-4 py-2"><span class="px-2 py-1 rounded text-xs font-semibold ${statusColor}">${statusText}</span></td>
                <td class="px-4 py-2 text-xs">${attendance.check_in_location || '---'}</td>
            `;
            table.appendChild(row);
        });
    }

    // Show message helper
    function showMessage(elementId, message, type) {
        const element = document.getElementById(elementId);
        element.textContent = message;
        element.className = `mt-3 text-sm font-semibold ${type === 'success' ? 'text-green-600' : 'text-red-600'}`;
    }

    // Handle location error
    function handleLocationError(error) {
        console.error('Geolocation error:', error);
        locationAvailable = false;
        setCheckButtonsEnabled(false);

        if (locationWatchId !== null) {
            navigator.geolocation.clearWatch(locationWatchId);
            locationWatchId = null;
        }

        let message = 'Không thể lấy vị trí. Vui lòng kiểm tra cài đặt vị trí của trình duyệt.';
        switch (error.code) {
            case error.PERMISSION_DENIED:
                message = 'Bạn đã từ chối quyền truy cập vị trí. Vui lòng bật quyền vị trí để chấm công.';
                break;
            case error.POSITION_UNAVAILABLE:
                message = 'Không thể lấy vị trí. Vui lòng kiểm tra kết nối mạng và GPS.';
                break;
            case error.TIMEOUT:
                message = 'Quá trình lấy vị trí đã hết thời gian chờ. Vui lòng thử lại.';
                break;
        }

        showMessage('check-in-message', message, 'error');
        // Show fallback controls for manual entry / skip
        const fallback = document.getElementById('location-fallback');
        if (fallback) fallback.classList.remove('hidden');
    }

    // Show manual location form
    function showManualLocationForm() {
        const form = document.getElementById('manual-loc-form');
        if (form) form.classList.toggle('hidden');
    }

    // Allow check-in/out without location after user confirmation
    function allowWithoutLocation() {
        if (!confirm('Bạn có chắc muốn chấm công mà không gửi vị trí? Điều này có thể yêu cầu xác minh thêm từ HR.')) return;
        // Enable buttons and set a flag so server can record missing location
        locationAvailable = false;
        setCheckButtonsEnabled(true);
        // mark UI to indicate missing location
        showMessage('check-in-message', 'Đã cho phép chấm công không cần vị trí. Vui lòng ghi chú lý do nếu cần.', 'success');
    }

    // Submit manual coordinates as check-in
    function submitManualCheckIn() {
        const lat = parseFloat(document.getElementById('manual-lat').value);
        const lon = parseFloat(document.getElementById('manual-lon').value);
        if (!lat || !lon || isNaN(lat) || isNaN(lon)) {
            alert('Vui lòng nhập tọa độ hợp lệ.');
            return;
        }
        // temporarily set current coords and perform check-in
        currentLatitude = lat;
        currentLongitude = lon;
        performCheckInFromUI();
    }

    function submitManualCheckOut() {
        const lat = parseFloat(document.getElementById('manual-lat').value);
        const lon = parseFloat(document.getElementById('manual-lon').value);
        if (!lat || !lon || isNaN(lat) || isNaN(lon)) {
            alert('Vui lòng nhập tọa độ hợp lệ.');
            return;
        }
        currentLatitude = lat;
        currentLongitude = lon;
        performCheckOutFromUI();
    }

    // Wrapper functions to call existing performCheckIn/Out but catch errors
    function performCheckInFromUI() {
        const notes = document.getElementById('check-in-notes').value;
        fetch('/api/employee/attendance/check-in', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            credentials: 'include',
            body: JSON.stringify({ latitude: currentLatitude, longitude: currentLongitude, notes })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showMessage('check-in-message', data.message, 'success');
                document.getElementById('check-in-notes').value = '';
                loadTodayAttendance();
                setTimeout(() => loadAttendanceHistory(), 500);
            } else {
                showMessage('check-in-message', data.message || 'Lỗi khi chấm công', 'error');
            }
        })
        .catch(e => { console.error(e); showMessage('check-in-message', 'Có lỗi xảy ra', 'error'); });
    }

    function performCheckOutFromUI() {
        const notes = document.getElementById('check-out-notes').value;
        fetch('/api/employee/attendance/check-out', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            credentials: 'include',
            body: JSON.stringify({ latitude: currentLatitude, longitude: currentLongitude, notes })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showMessage('check-out-message', data.message, 'success');
                document.getElementById('check-out-notes').value = '';
                loadTodayAttendance();
                setTimeout(() => loadAttendanceHistory(), 500);
            } else {
                showMessage('check-out-message', data.message || 'Lỗi khi chấm công', 'error');
            }
        })
        .catch(e => { console.error(e); showMessage('check-out-message', 'Có lỗi xảy ra', 'error'); });
    }

    // Get auth token from localStorage or session
    function getToken() {
        // Cố gắng lấy từ localStorage
        let token = localStorage.getItem('api_token');
        if (token) return token;

        // Hoặc từ meta tag nếu có
        const metaToken = document.querySelector('meta[name="api-token"]');
        if (metaToken) return metaToken.getAttribute('content');

        // Hoặc từ cookie
        const cookies = document.cookie.split(';');
        for (let cookie of cookies) {
            const [name, value] = cookie.trim().split('=');
            if (name === 'XSRF-TOKEN' || name === 'api_token') {
                return decodeURIComponent(value);
            }
        }

        return '';
    }

    // Face attendance helpers
    let faceStream = null;
    let faceCapturedImage = null;

    async function loadFaceProfile() {
        try {
            const response = await fetch('/api/employee/attendance/face-profile', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include',
            });
            const data = await response.json();

            if (data.success && data.face_profile) {
                document.getElementById('face-registration-status').textContent = 'Đã đăng ký khuôn mặt';
                document.getElementById('face-registration-status').classList.remove('text-blue-600');
                document.getElementById('face-registration-status').classList.add('text-green-600');
            } else {
                document.getElementById('face-registration-status').textContent = 'Chưa đăng ký khuôn mặt';
                document.getElementById('face-registration-status').classList.remove('text-blue-600');
                document.getElementById('face-registration-status').classList.add('text-red-600');
            }
        } catch (error) {
            console.error('Face profile error:', error);
            document.getElementById('face-registration-status').textContent = 'Không thể kiểm tra đăng ký';
            document.getElementById('face-registration-status').classList.remove('text-blue-600');
            document.getElementById('face-registration-status').classList.add('text-yellow-600');
        }
    }

    async function initializeFaceCamera() {
        try {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('Trình duyệt không hỗ trợ camera');
            }

            const constraints = { video: { facingMode: 'user' } };
            faceStream = await navigator.mediaDevices.getUserMedia(constraints);
            const video = document.getElementById('face-video');
            video.srcObject = faceStream;
            video.play();

            document.getElementById('face-preview-panel').classList.remove('hidden');
            document.getElementById('capture-face-btn').disabled = false;
            document.getElementById('face-status-message').textContent = 'Camera đã sẵn sàng. Vui lòng đưa khuôn mặt vào khung hình và chụp.';
        } catch (error) {
            console.error('Camera error:', error);
            document.getElementById('face-status-message').textContent = 'Không thể mở camera: ' + error.message;
        }
    }

    function captureFaceImage() {
        const video = document.getElementById('face-video');
        const canvas = document.getElementById('face-canvas');
        const preview = document.getElementById('face-preview');

        if (!video || !canvas || !preview) {
            return;
        }

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const context = canvas.getContext('2d');
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        faceCapturedImage = canvas.toDataURL('image/jpeg', 0.85);
        preview.src = faceCapturedImage;
        preview.alt = 'Ảnh chụp khuôn mặt';
        preview.classList.remove('hidden');

        document.getElementById('register-face-btn').disabled = false;
        document.getElementById('face-attendance-btn').disabled = false;
        document.getElementById('face-status-message').textContent = 'Đã chụp ảnh. Bạn có thể đăng ký hoặc chấm công bằng khuôn mặt.';
    }

    async function registerFace() {
        if (!faceCapturedImage) {
            showFaceMessage('Vui lòng chụp ảnh trước khi đăng ký.');
            return;
        }

        try {
            const response = await fetch('/api/employee/attendance/register-face', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                credentials: 'include',
                body: JSON.stringify({ face_image: faceCapturedImage }),
            });
            const data = await response.json();

            if (data.success) {
                showFaceMessage(data.message, true);
                document.getElementById('face-registration-status').textContent = 'Đã đăng ký khuôn mặt';
                document.getElementById('face-registration-status').classList.remove('text-red-600', 'text-yellow-600');
                document.getElementById('face-registration-status').classList.add('text-green-600');
            } else {
                showFaceMessage(data.message || 'Đăng ký khuôn mặt không thành công.');
            }
        } catch (error) {
            console.error('Register face error:', error);
            showFaceMessage('Lỗi khi đăng ký khuôn mặt. Vui lòng thử lại.');
        }
    }

    async function submitFaceAttendance() {
        if (!faceCapturedImage) {
            showFaceMessage('Vui lòng chụp ảnh khuôn mặt trước khi chấm công.');
            return;
        }

        const notes = document.getElementById('check-in-notes').value || document.getElementById('check-out-notes').value || null;

        try {
            const response = await fetch('/api/employee/attendance/face', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                credentials: 'include',
                body: JSON.stringify({
                    face_image: faceCapturedImage,
                    latitude: currentLatitude || null,
                    longitude: currentLongitude || null,
                    notes,
                }),
            });
            const data = await response.json();

            if (data.success) {
                showFaceMessage(data.message, true);
                loadTodayAttendance();
                setTimeout(() => loadAttendanceHistory(), 500);
            } else {
                showFaceMessage(data.message || 'Chấm công bằng khuôn mặt thất bại.');
            }
        } catch (error) {
            console.error('Face attendance error:', error);
            showFaceMessage('Lỗi khi chấm công bằng khuôn mặt. Vui lòng thử lại.');
        }
    }

    function showFaceMessage(message, success = false) {
        const element = document.getElementById('face-status-message');
        element.textContent = message;
        element.className = success ? 'text-sm text-green-600' : 'text-sm text-red-600';
    }

    function stopFaceCamera() {
        if (faceStream) {
            faceStream.getTracks().forEach(track => track.stop());
            faceStream = null;
        }
    }

    window.addEventListener('beforeunload', stopFaceCamera);
</script>
</script>

@endsection
