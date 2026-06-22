/**
 * Geolocation and Attendance API Utility Module
 * Handles location tracking, distance calculation, and API communications
 */

export class GeolocationService {
    constructor(options = {}) {
        this.options = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0,
            ...options
        };
        this.watchId = null;
        this.lastPosition = null;
        this.listeners = [];
    }

    /**
     * Start watching user position
     */
    startWatch(onPositionChange) {
        if (!navigator.geolocation) {
            throw new Error('Geolocation is not supported by this browser');
        }

        this.watchId = navigator.geolocation.watchPosition(
            (position) => {
                this.lastPosition = position;
                onPositionChange(position);
            },
            (error) => this.handleError(error),
            this.options
        );

        return this.watchId;
    }

    /**
     * Stop watching position
     */
    stopWatch() {
        if (this.watchId) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }
    }

    /**
     * Get current position once
     */
    getCurrentPosition() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocation is not supported by this browser'));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.lastPosition = position;
                    resolve(position);
                },
                (error) => reject(this.handleError(error)),
                this.options
            );
        });
    }

    /**
     * Handle geolocation errors
     */
    handleError(error) {
        let message = 'Lỗi không xác định';
        
        switch (error.code) {
            case error.PERMISSION_DENIED:
                message = 'Bạn đã từ chối quyền truy cập vị trí. Vui lòng bật quyền vị trí trong cài đặt trình duyệt.';
                break;
            case error.POSITION_UNAVAILABLE:
                message = 'Không thể lấy được vị trí. Vui lòng kiểm tra kết nối mạng và GPS.';
                break;
            case error.TIMEOUT:
                message = 'Quá trình lấy vị trí đã hết thời gian chờ. Vui lòng thử lại.';
                break;
        }

        console.error('Geolocation error:', error);
        return new Error(message);
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     * @param {number} lat1 - Latitude of first point
     * @param {number} lon1 - Longitude of first point
     * @param {number} lat2 - Latitude of second point
     * @param {number} lon2 - Longitude of second point
     * @return {number} Distance in meters
     */
    static calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371000; // Earth radius in meters
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    /**
     * Get distance from current position to a target location
     */
    getDistanceFromCurrent(targetLat, targetLon) {
        if (!this.lastPosition) {
            return null;
        }

        return GeolocationService.calculateDistance(
            this.lastPosition.coords.latitude,
            this.lastPosition.coords.longitude,
            targetLat,
            targetLon
        );
    }

    /**
     * Check if position is within allowed radius
     */
    isWithinRadius(currentLat, currentLon, targetLat, targetLon, radiusMeters) {
        const distance = GeolocationService.calculateDistance(
            currentLat,
            currentLon,
            targetLat,
            targetLon
        );
        return distance <= radiusMeters;
    }

    /**
     * Convert coordinates to address using reverse geocoding
     * Note: This requires an API key for a geocoding service
     */
    async reverseGeocode(lat, lon) {
        try {
            // Using OpenStreetMap Nominatim API (free, no key required)
            const response = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`
            );
            const data = await response.json();
            return data.address || `${lat.toFixed(6)}, ${lon.toFixed(6)}`;
        } catch (error) {
            console.error('Reverse geocoding error:', error);
            return `${lat.toFixed(6)}, ${lon.toFixed(6)}`;
        }
    }
}

export class AttendanceAPI {
    constructor(baseUrl = '/api/employee/attendance', getToken = null) {
        this.baseUrl = baseUrl;
        this.getToken = getToken || (() => localStorage.getItem('api_token'));
    }

    /**
     * Get common headers for API requests
     */
    getHeaders() {
        return {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${this.getToken()}`,
        };
    }

    /**
     * Get today's attendance record
     */
    async getTodayAttendance() {
        const response = await fetch(`${this.baseUrl}/today`, {
            headers: this.getHeaders(),
        });

        if (!response.ok) {
            throw new Error('Failed to fetch today attendance');
        }

        return await response.json();
    }

    /**
     * Perform check-in
     */
    async checkIn(latitude, longitude, notes = '') {
        const response = await fetch(`${this.baseUrl}/check-in`, {
            method: 'POST',
            headers: this.getHeaders(),
            body: JSON.stringify({
                latitude,
                longitude,
                notes,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Check-in failed');
        }

        return data;
    }

    /**
     * Perform check-out
     */
    async checkOut(latitude, longitude, notes = '') {
        const response = await fetch(`${this.baseUrl}/check-out`, {
            method: 'POST',
            headers: this.getHeaders(),
            body: JSON.stringify({
                latitude,
                longitude,
                notes,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Check-out failed');
        }

        return data;
    }

    /**
     * Get attendance history for a month
     */
    async getHistory(month, year) {
        const response = await fetch(
            `${this.baseUrl}/history?month=${month}&year=${year}`,
            { headers: this.getHeaders() }
        );

        if (!response.ok) {
            throw new Error('Failed to fetch attendance history');
        }

        return await response.json();
    }

    /**
     * Get office location settings
     */
    async getOfficeLocation() {
        const response = await fetch(`${this.baseUrl}/office-location`, {
            headers: this.getHeaders(),
        });

        if (!response.ok) {
            throw new Error('Failed to fetch office location');
        }

        return await response.json();
    }
}

export class AttendanceUIManager {
    constructor(containerSelector, geolocationService, attendanceAPI) {
        this.container = document.querySelector(containerSelector);
        this.geolocationService = geolocationService;
        this.attendanceAPI = attendanceAPI;
        this.officeLocation = null;
        this.todayAttendance = null;
    }

    /**
     * Initialize the attendance UI
     */
    async initialize() {
        try {
            await this.loadOfficeLocation();
            await this.loadTodayAttendance();
            await this.geolocationService.getCurrentPosition();
        } catch (error) {
            this.showError('Initialization failed: ' + error.message);
        }
    }

    /**
     * Load office location
     */
    async loadOfficeLocation() {
        const data = await this.attendanceAPI.getOfficeLocation();
        this.officeLocation = data;
        return data;
    }

    /**
     * Load today's attendance
     */
    async loadTodayAttendance() {
        const data = await this.attendanceAPI.getTodayAttendance();
        this.todayAttendance = data.attendance;
        this.updateAttendanceDisplay();
        return data;
    }

    /**
     * Update attendance display
     */
    updateAttendanceDisplay() {
        if (!this.todayAttendance) return;

        if (this.todayAttendance.check_in) {
            const checkInTime = new Date(this.todayAttendance.check_in);
            this.updateElement('check-in-time', checkInTime.toLocaleTimeString('vi-VN'));
            this.updateElement('check-in-status', `Khoảng cách: ${this.todayAttendance.check_in_distance}m`);
            this.setButtonDisabled('check-in-btn', true);
            this.setButtonDisabled('check-out-btn', false);
        }

        if (this.todayAttendance.check_out) {
            const checkOutTime = new Date(this.todayAttendance.check_out);
            this.updateElement('check-out-time', checkOutTime.toLocaleTimeString('vi-VN'));
            this.updateElement('check-out-status', `Khoảng cách: ${this.todayAttendance.check_out_distance}m`);
            this.setButtonDisabled('check-out-btn', true);
        }
    }

    /**
     * Perform check-in
     */
    async performCheckIn(latitude, longitude, notes = '') {
        try {
            const result = await this.attendanceAPI.checkIn(latitude, longitude, notes);
            this.showSuccess(result.message);
            await this.loadTodayAttendance();
            this.clearNotes('check-in-notes');
            return result;
        } catch (error) {
            this.showError(error.message);
            throw error;
        }
    }

    /**
     * Perform check-out
     */
    async performCheckOut(latitude, longitude, notes = '') {
        try {
            const result = await this.attendanceAPI.checkOut(latitude, longitude, notes);
            this.showSuccess(result.message);
            await this.loadTodayAttendance();
            this.clearNotes('check-out-notes');
            return result;
        } catch (error) {
            this.showError(error.message);
            throw error;
        }
    }

    /**
     * Update distance display
     */
    updateDistanceDisplay(currentLat, currentLon) {
        if (!this.officeLocation) return;

        const distance = GeolocationService.calculateDistance(
            currentLat,
            currentLon,
            this.officeLocation.office_latitude,
            this.officeLocation.office_longitude
        );

        const isWithinRadius = distance <= this.officeLocation.allowed_distance;
        
        this.updateElement('current-distance', 
            `${Math.round(distance)}/${this.officeLocation.allowed_distance}m`);
        
        if (isWithinRadius) {
            this.hideDistanceAlert();
        } else {
            this.showDistanceAlert(distance);
        }

        return distance;
    }

    /**
     * Show distance alert
     */
    showDistanceAlert(distance) {
        const alertElement = document.getElementById('distance-alert');
        if (alertElement) {
            alertElement.classList.remove('hidden');
            document.getElementById('distance-alert-message').textContent = 
                `Bạn đang cách văn phòng ${Math.round(distance)}m. Không thể chấm công ngoài phạm vi ${this.officeLocation.allowed_distance}m.`;
        }
    }

    /**
     * Hide distance alert
     */
    hideDistanceAlert() {
        const alertElement = document.getElementById('distance-alert');
        if (alertElement) {
            alertElement.classList.add('hidden');
        }
    }

    /**
     * Helper methods
     */
    updateElement(elementId, content) {
        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = content;
        }
    }

    setButtonDisabled(buttonId, disabled) {
        const button = document.getElementById(buttonId);
        if (button) {
            button.disabled = disabled;
            if (disabled) {
                button.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                button.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
    }

    clearNotes(textareaId) {
        const textarea = document.getElementById(textareaId);
        if (textarea) {
            textarea.value = '';
        }
    }

    showSuccess(message) {
        console.log('Success:', message);
        // Implement toast notification if available
    }

    showError(message) {
        console.error('Error:', message);
        // Implement toast notification if available
    }
}

