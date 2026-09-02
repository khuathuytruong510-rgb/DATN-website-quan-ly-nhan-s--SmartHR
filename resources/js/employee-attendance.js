import * as faceapi from '@vladmandic/face-api';

const MODEL_URL = '/models/face-api';
const SAMPLE_COUNT = 3;
const DETECT_OPTIONS = new faceapi.TinyFaceDetectorOptions({
    inputSize: 224,
    scoreThreshold: 0.5,
});

const state = {
    map: null,
    userMarker: null,
    officeCircle: null,
    officeLat: null,
    officeLng: null,
    allowedDistance: 60,
    latitude: null,
    longitude: null,
    withinRange: false,
    registered: false,
    pending: false,
    modelsReady: false,
    cameraReady: false,
    faceOk: false,
    lastDetection: null,
    today: null,
    busy: false,
    stream: null,
    detecting: false,
};

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const approverLabel = () => document.getElementById('employee-attendance')?.dataset.approverLabel || 'HR';

const headers = (json = true) => ({
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN': csrf(),
    ...(json ? { 'Content-Type': 'application/json' } : {}),
});

function $(id) {
    return document.getElementById(id);
}

function setMessage(text, ok = false) {
    const el = $('face-status-message');
    if (!el) {
        return;
    }
    el.textContent = text || '';
    el.className = ok ? 'text-sm text-green-600' : 'text-sm text-red-600';
}

function setGuide(text) {
    const el = $('face-guide');
    if (el) {
        el.textContent = text;
    }
}

function updateClock() {
    const el = $('attendance-now');
    if (el) {
        el.textContent = new Date().toLocaleString('vi-VN');
    }
}

function formatTime(value) {
    if (!value) {
        return '--:--:--';
    }
    return new Date(value).toLocaleTimeString('vi-VN');
}

function haversine(lat1, lon1, lat2, lon2) {
    const R = 6371000;
    const dLat = ((lat2 - lat1) * Math.PI) / 180;
    const dLon = ((lon2 - lon1) * Math.PI) / 180;
    const a = Math.sin(dLat / 2) ** 2
        + Math.cos((lat1 * Math.PI) / 180) * Math.cos((lat2 * Math.PI) / 180) * Math.sin(dLon / 2) ** 2;
    return R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
}

function averageDescriptors(samples) {
    const size = samples[0].length;
    const avg = new Array(size).fill(0);
    samples.forEach((sample) => {
        for (let i = 0; i < size; i += 1) {
            avg[i] += sample[i];
        }
    });
    for (let i = 0; i < size; i += 1) {
        avg[i] /= samples.length;
    }
    const norm = Math.sqrt(avg.reduce((sum, value) => sum + value * value, 0));
    return norm > 0 ? avg.map((value) => value / norm) : avg;
}

function captureThumbnail(video) {
    const canvas = document.createElement('canvas');
    const size = 240;
    canvas.width = size;
    canvas.height = size;
    const ctx = canvas.getContext('2d');
    const min = Math.min(video.videoWidth, video.videoHeight);
    const sx = (video.videoWidth - min) / 2;
    const sy = (video.videoHeight - min) / 2;
    ctx.drawImage(video, sx, sy, min, min, 0, 0, size, size);
    return canvas.toDataURL('image/jpeg', 0.7);
}

function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

function updateButtons() {
    const registerBtn = $('register-face-btn');
    const punchBtn = $('punch-face-btn');
    if (!registerBtn || !punchBtn) {
        return;
    }

    registerBtn.textContent = state.pending
        ? `Gửi lại khuôn mặt cho ${approverLabel()}`
        : (state.registered ? 'Đăng ký lại khuôn mặt' : `Gửi khuôn mặt cho ${approverLabel()} duyệt`);
    registerBtn.disabled = state.busy || !state.modelsReady || !state.cameraReady;

    const alreadyDone = Boolean(state.today?.check_in && state.today?.check_out);
    punchBtn.disabled = state.busy
        || !state.registered
        || !state.modelsReady
        || !state.cameraReady
        || !state.withinRange
        || alreadyDone;

    if (alreadyDone) {
        punchBtn.textContent = 'Đã chấm đủ hôm nay';
    } else if (state.today?.check_in) {
        punchBtn.textContent = 'Chấm công ra';
    } else {
        punchBtn.textContent = 'Chấm công vào';
    }
}

function setRegistrationUi(registered, image, pending = false) {
    state.registered = registered;
    state.pending = pending;
    const badge = $('face-registration-status');
    const thumb = $('registered-face');
    if (registered && !pending) {
        badge.textContent = 'Đã duyệt khuôn mặt';
        badge.className = 'text-sm font-semibold text-green-600';
    } else if (pending) {
        badge.textContent = registered
            ? `Đã duyệt — đang chờ ${approverLabel()} duyệt ảnh mới`
            : `Đang chờ ${approverLabel()} duyệt`;
        badge.className = 'text-sm font-semibold text-amber-600';
    } else {
        badge.textContent = 'Chưa đăng ký khuôn mặt';
        badge.className = 'text-sm font-semibold text-red-600';
    }
    if (image && thumb) {
        thumb.src = image;
        thumb.classList.remove('hidden');
    } else if (thumb && !registered && !pending) {
        thumb.classList.add('hidden');
        thumb.removeAttribute('src');
    }
    updateButtons();
}

function updateAttendanceCards() {
    const attendance = state.today;
    $('check-in-time').textContent = formatTime(attendance?.check_in);
    $('check-out-time').textContent = formatTime(attendance?.check_out);

    if (attendance?.check_in) {
        const distance = attendance.check_in_distance != null ? `${attendance.check_in_distance}m` : '';
        $('check-in-status').textContent = distance ? `Khoảng cách: ${distance}` : 'Đã chấm vào';
    } else {
        $('check-in-status').textContent = 'Chưa chấm công';
    }

    if (attendance?.check_out) {
        const distance = attendance.check_out_distance != null ? `${attendance.check_out_distance}m` : '';
        $('check-out-status').textContent = distance ? `Khoảng cách: ${distance}` : 'Đã chấm ra';
    } else {
        $('check-out-status').textContent = attendance?.check_in ? 'Chưa chấm ra' : 'Chưa chấm công';
    }

    updateButtons();
}

function updateDistanceUi() {
    if (state.latitude == null || state.longitude == null || state.officeLat == null) {
        return;
    }

    const distance = haversine(state.latitude, state.longitude, state.officeLat, state.officeLng);
    state.withinRange = distance <= state.allowedDistance;
    $('current-distance').textContent = `${Math.round(distance)}/${Math.round(state.allowedDistance)}m`;
    $('current-location').textContent = `${state.latitude.toFixed(6)}, ${state.longitude.toFixed(6)}`;

    const alertBox = $('distance-alert');
    if (state.withinRange) {
        alertBox.classList.add('hidden');
    } else {
        alertBox.classList.remove('hidden');
        $('distance-alert-message').textContent = `Bạn đang cách văn phòng ${Math.round(distance)}m. Chỉ được chấm công trong phạm vi ${Math.round(state.allowedDistance)}m.`;
    }

    if (state.userMarker) {
        state.userMarker.setLatLng([state.latitude, state.longitude]);
    } else if (state.map && window.L) {
        state.userMarker = window.L.marker([state.latitude, state.longitude]).addTo(state.map).bindPopup('Vị trí của bạn');
    }

    updateButtons();
}

function initMap() {
    if (!window.L || !$('map')) {
        return;
    }

    const lat = state.officeLat ?? 21.0285;
    const lng = state.officeLng ?? 105.8542;
    state.map = window.L.map('map').setView([lat, lng], 17);
    window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(state.map);

    if (state.officeLat != null) {
        window.L.marker([state.officeLat, state.officeLng]).addTo(state.map).bindPopup('Văn phòng');
        state.officeCircle = window.L.circle([state.officeLat, state.officeLng], {
            color: '#2563eb',
            fillColor: '#3b82f6',
            fillOpacity: 0.15,
            radius: state.allowedDistance,
        }).addTo(state.map);
    }

    setTimeout(() => state.map?.invalidateSize(), 200);
}

async function api(url, options = {}) {
    const response = await fetch(url, {
        credentials: 'include',
        ...options,
        headers: { ...headers(Boolean(options.body)), ...(options.headers || {}) },
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok && !data.message) {
        data.message = 'Không thể kết nối máy chủ.';
        data.success = false;
    }
    return data;
}

async function loadOffice() {
    const data = await api('/api/employee/attendance/office-location');
    if (!data.success) {
        return;
    }
    state.officeLat = data.office_latitude;
    state.officeLng = data.office_longitude;
    state.allowedDistance = data.allowed_distance || 60;
    $('current-distance').textContent = `--/${Math.round(state.allowedDistance)}m`;
}

async function loadToday() {
    const data = await api('/api/employee/attendance/today');
    if (!data.success) {
        if (data.message) {
            setMessage(data.message);
        }
        return;
    }
    state.today = data.attendance;
    updateAttendanceCards();
}

async function loadHistory() {
    const now = new Date();
    const data = await api(`/api/employee/attendance/history?month=${now.getMonth() + 1}&year=${now.getFullYear()}`);
    const table = $('history-table');
    if (!data.success || !Array.isArray(data.attendances) || data.attendances.length === 0) {
        table.innerHTML = '<tr><td class="px-4 py-2 text-center" colspan="6">Không có dữ liệu</td></tr>';
        return;
    }

    table.innerHTML = '';
    data.attendances.forEach((row) => {
        const statusClass = {
            present: 'bg-green-100 text-green-800',
            late: 'bg-yellow-100 text-yellow-800',
            absent: 'bg-red-100 text-red-800',
            leave: 'bg-blue-100 text-blue-800',
            leave_early: 'bg-blue-100 text-blue-800',
        }[row.status] || 'bg-gray-100 text-gray-800';
        const statusText = {
            present: 'Đúng giờ',
            late: 'Trễ',
            absent: 'Vắng',
            leave: 'Nghỉ',
            leave_early: 'Về sớm',
            late_and_leave_early: 'Trễ & về sớm',
            overtime: 'Tăng ca',
        }[row.status] || row.status;
        const adjust = row.pending_adjustment
            ? `<span class="text-xs text-amber-700">Đang chờ ${approverLabel()}</span>`
            : `<button type="button" class="text-xs font-semibold text-indigo-600" data-adjust="${row.id}" data-date="${new Date(row.date).toLocaleDateString('vi-VN')}">Yêu cầu điều chỉnh</button>`;

        const tr = document.createElement('tr');
        tr.className = 'border-t border-gray-200 hover:bg-gray-50';
        tr.innerHTML = `
            <td class="px-4 py-2">${new Date(row.date).toLocaleDateString('vi-VN')}</td>
            <td class="px-4 py-2">${formatTime(row.check_in)}</td>
            <td class="px-4 py-2">${formatTime(row.check_out)}</td>
            <td class="px-4 py-2"><span class="px-2 py-1 rounded text-xs font-semibold ${statusClass}">${statusText}</span></td>
            <td class="px-4 py-2 text-xs">${row.check_in_location || '---'}</td>
            <td class="px-4 py-2">${adjust}</td>
        `;
        table.appendChild(tr);
    });
}

async function loadFaceProfile() {
    const data = await api('/api/employee/attendance/face-profile');
    if (!data.success) {
        $('face-registration-status').textContent = data.message || 'Không thể kiểm tra đăng ký';
        $('face-registration-status').className = 'text-sm font-semibold text-yellow-600';
        return;
    }
    setRegistrationUi(Boolean(data.registered), data.face_image, Boolean(data.pending));
}

function startGps() {
    if (!navigator.geolocation) {
        $('current-location').textContent = 'Trình duyệt không hỗ trợ GPS';
        setMessage('Trình duyệt không hỗ trợ GPS. Không thể chấm công.');
        return;
    }

    const apply = (position) => {
        state.latitude = position.coords.latitude;
        state.longitude = position.coords.longitude;
        updateDistanceUi();
        if (state.map) {
            state.map.setView([state.latitude, state.longitude], 17);
        }
    };

    navigator.geolocation.getCurrentPosition(apply, (error) => {
        $('current-location').textContent = 'Chưa có quyền vị trí';
        setMessage(error.code === 1
            ? 'Hãy cho phép truy cập vị trí để chấm công.'
            : 'Không lấy được GPS. Kiểm tra kết nối rồi tải lại trang.');
    }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });

    navigator.geolocation.watchPosition(apply, () => {}, {
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 3000,
    });
}

async function loadModels() {
    setGuide('Đang tải mô hình nhận diện...');
    await Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
        faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
        faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
    ]);
    state.modelsReady = true;
}

function cameraErrorMessage(error) {
    const name = error?.name || '';
    const raw = `${error?.message || ''} ${name}`.toLowerCase();

    if (name === 'NotAllowedError' || raw.includes('permission') || raw.includes('denied')) {
        return 'Trình duyệt đang chặn camera. Hãy bấm biểu tượng camera trên thanh địa chỉ và cho phép, rồi bấm Mở lại camera.';
    }
    if (name === 'NotFoundError' || raw.includes('requested device not found')) {
        return 'Không tìm thấy webcam. Cắm camera rồi bấm Mở lại camera.';
    }
    if (name === 'NotReadableError' || name === 'TrackStartError' || raw.includes('device in use') || raw.includes('could not start')) {
        return 'Camera đang bị ứng dụng khác chiếm (Chrome/Cursor/Zoom/Teams). Đóng tab/app đó, rồi bấm Mở lại camera.';
    }
    if (name === 'OverconstrainedError') {
        return 'Webcam không hỗ trợ độ phân giải này. Bấm Mở lại camera để thử lại.';
    }
    if (window.isSecureContext === false) {
        return 'Camera chỉ chạy trên http://127.0.0.1 hoặc https. Hãy mở đúng địa chỉ đó.';
    }

    return error?.message ? `Không mở được camera: ${error.message}` : 'Không mở được camera.';
}

function stopCamera() {
    state.cameraReady = false;
    state.faceOk = false;
    state.lastDetection = null;

    if (state.stream) {
        state.stream.getTracks().forEach((track) => {
            try {
                track.stop();
            } catch {
                // ignore
            }
        });
        state.stream = null;
    }

    const video = $('face-video');
    if (video) {
        video.srcObject = null;
    }
}

async function requestCameraStream() {
    const attempts = [
        { video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } }, audio: false },
        { video: { facingMode: 'user' }, audio: false },
        { video: true, audio: false },
    ];

    let lastError = null;
    for (const constraints of attempts) {
        try {
            return await navigator.mediaDevices.getUserMedia(constraints);
        } catch (error) {
            lastError = error;
            if (error?.name === 'NotAllowedError') {
                throw error;
            }
        }
    }
    throw lastError || new Error('Không mở được camera.');
}

async function startCamera() {
    const video = $('face-video');
    if (!video) {
        return;
    }
    if (!navigator.mediaDevices?.getUserMedia) {
        setGuide('Trình duyệt không hỗ trợ camera.');
        setMessage('Trình duyệt không hỗ trợ camera. Hãy dùng Chrome hoặc Edge.');
        return;
    }

    stopCamera();
    setGuide('Đang mở camera...');
    setMessage('');

    const stream = await requestCameraStream();
    state.stream = stream;
    video.srcObject = stream;
    video.muted = true;
    video.playsInline = true;
    await video.play();
    state.cameraReady = true;
    setGuide('Đưa khuôn mặt vào giữa khung hình.');
    updateButtons();

    if (!state.detecting) {
        state.detecting = true;
        detectionLoop();
    }
}

async function retryCamera() {
    try {
        if (!state.modelsReady) {
            setGuide('Đang tải mô hình nhận diện...');
            await loadModels();
        }
        await startCamera();
        setMessage('Camera đã sẵn sàng. Nhìn thẳng khung hình để đăng ký.', true);
    } catch (error) {
        setGuide('Không mở được camera.');
        setMessage(cameraErrorMessage(error));
        updateButtons();
    }
}

function faceQuality(detection, video) {
    if (!detection) {
        return 'Không thấy khuôn mặt. Nhìn thẳng camera.';
    }
    const box = detection.detection.box;
    if (box.width < video.videoWidth * 0.22) {
        return 'Lại gần camera hơn.';
    }
    return null;
}

async function detectFace() {
    const video = $('face-video');
    if (!state.modelsReady || !state.cameraReady || video.readyState < 2) {
        return null;
    }
    return faceapi
        .detectSingleFace(video, DETECT_OPTIONS)
        .withFaceLandmarks()
        .withFaceDescriptor();
}

function drawOverlay(detection) {
    const video = $('face-video');
    const canvas = $('face-overlay');
    if (!canvas || !video.videoWidth) {
        return;
    }
    canvas.width = video.clientWidth;
    canvas.height = video.clientHeight;
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    if (!detection) {
        return;
    }
    const scaleX = canvas.width / video.videoWidth;
    const scaleY = canvas.height / video.videoHeight;
    const box = detection.detection.box;
    ctx.strokeStyle = state.faceOk ? '#22c55e' : '#f59e0b';
    ctx.lineWidth = 3;
    ctx.strokeRect(box.x * scaleX, box.y * scaleY, box.width * scaleX, box.height * scaleY);
}

async function detectionLoop() {
    try {
        const detection = await detectFace();
        const issue = faceQuality(detection, $('face-video'));
        state.lastDetection = detection;
        state.faceOk = Boolean(detection) && !issue;
        drawOverlay(detection);
        if (!state.modelsReady) {
            setGuide('Đang tải mô hình nhận diện...');
        } else if (!detection) {
            setGuide('Không thấy khuôn mặt. Nhìn thẳng camera.');
        } else if (issue) {
            setGuide(issue);
        } else {
            setGuide('Khuôn mặt ổn. Có thể đăng ký hoặc chấm công.');
        }
    } catch {
        // keep looping
    }
    setTimeout(detectionLoop, 280);
}

async function collectSamples() {
    const samples = [];
    for (let i = 0; i < SAMPLE_COUNT; i += 1) {
        const detection = await detectFace();
        const issue = faceQuality(detection, $('face-video'));
        if (!detection || issue) {
            throw new Error(issue || 'Không nhận được khuôn mặt. Hãy giữ mặt trong khung rồi thử lại.');
        }
        samples.push(Array.from(detection.descriptor));
        setGuide(`Đã lấy mẫu ${i + 1}/${SAMPLE_COUNT}...`);
        await sleep(350);
    }
    return averageDescriptors(samples);
}

async function registerFace() {
    if (state.busy) {
        return;
    }
    state.busy = true;
    updateButtons();
    setMessage('Đang lấy mẫu khuôn mặt...');
    try {
        const embedding = await collectSamples();
        const data = await api('/api/employee/attendance/register-face', {
            method: 'POST',
            body: JSON.stringify({
                face_embedding: JSON.stringify(embedding),
                face_image: captureThumbnail($('face-video')),
            }),
        });
        if (!data.success) {
            throw new Error(data.message || 'Đăng ký không thành công.');
        }
        setRegistrationUi(
            Boolean(data.registered),
            data.face_image || captureThumbnail($('face-video')),
            Boolean(data.pending)
        );
        setMessage(data.message, true);
    } catch (error) {
        setMessage(error.message || 'Đăng ký khuôn mặt thất bại.');
    } finally {
        state.busy = false;
        updateButtons();
    }
}

async function punchFace() {
    if (state.busy) {
        return;
    }
    if (!state.registered) {
        setMessage('Hãy đăng ký khuôn mặt trước.');
        return;
    }
    if (state.latitude == null || state.longitude == null) {
        setMessage('Chưa có GPS. Hãy cho phép vị trí rồi thử lại.');
        return;
    }
    if (!state.withinRange) {
        setMessage(`Bạn đang ngoài phạm vi ${Math.round(state.allowedDistance)}m.`);
        return;
    }

    state.busy = true;
    updateButtons();
    setMessage('Đang nhận diện khuôn mặt...');
    try {
        const detection = await detectFace();
        const issue = faceQuality(detection, $('face-video'));
        if (!detection || issue) {
            throw new Error(issue || 'Không nhận diện được khuôn mặt.');
        }

        const data = await api('/api/employee/attendance/face', {
            method: 'POST',
            body: JSON.stringify({
                face_embedding: JSON.stringify(Array.from(detection.descriptor)),
                latitude: state.latitude,
                longitude: state.longitude,
                notes: $('attendance-notes').value || null,
            }),
        });
        if (!data.success) {
            throw new Error(data.message || 'Chấm công thất bại.');
        }
        state.today = data.attendance;
        $('attendance-notes').value = '';
        updateAttendanceCards();
        setMessage(data.message, true);
        await loadHistory();
    } catch (error) {
        setMessage(error.message || 'Chấm công thất bại.');
    } finally {
        state.busy = false;
        updateButtons();
    }
}

function bindEvents() {
    $('retry-camera-btn')?.addEventListener('click', retryCamera);
    $('register-face-btn')?.addEventListener('click', registerFace);
    $('punch-face-btn')?.addEventListener('click', punchFace);
    $('history-table')?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-adjust]');
        if (!button) {
            return;
        }
        const dialog = $('adjust-dialog');
        const form = $('adjust-form');
        $('adjust-date-label').textContent = `Ngày ${button.dataset.date} — bạn không tự sửa giờ.`;
        form.action = `/me/attendance/${button.dataset.adjust}/adjust`;
        dialog.showModal();
    });
    window.addEventListener('pagehide', stopCamera);
    window.addEventListener('beforeunload', stopCamera);
}

async function boot() {
    if (!$('employee-attendance')) {
        return;
    }

    updateClock();
    setInterval(updateClock, 1000);
    bindEvents();
    updateButtons();

    await loadOffice();
    initMap();
    startGps();
    await Promise.all([loadToday(), loadHistory(), loadFaceProfile()]);

    try {
        await loadModels();
        await startCamera();
        updateButtons();
    } catch (error) {
        setGuide('Không mở được camera.');
        setMessage(cameraErrorMessage(error));
        updateButtons();
    }
}

boot();
