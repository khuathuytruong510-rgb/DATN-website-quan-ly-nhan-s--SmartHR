@extends('layouts.app')

@section('title', 'Chấm công')

@section('breadcrumb')
<li><a href="{{ route('me.dashboard') }}">Dashboard</a></li>
<li>Chấm công</li>
@endsection

@section('content')
<link rel="stylesheet" href="/vendor/leaflet/leaflet.css" />

@php $approverLabel = $approverLabel ?? \App\Support\RequestApprover::queueLabel($employee ?? auth()->user()?->linkedEmployee()); @endphp
<div id="employee-attendance" class="container mx-auto px-4 py-8" data-approver-label="{{ $approverLabel }}">
    <div class="max-w-5xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Chấm công khuôn mặt</h1>
            <p class="text-gray-600">Hôm nay: <strong id="attendance-now">{{ date('d/m/Y H:i:s') }}</strong></p>
            @if(session('success'))
                <div class="mt-3 rounded-lg bg-green-50 text-green-800 px-4 py-2">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mt-3 rounded-lg bg-red-50 text-red-700 px-4 py-2">{{ session('error') }}</div>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow-md mb-6 overflow-hidden">
            <div id="map" style="width:100%;height:320px;"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-sm text-gray-600 mb-2">Chấm công vào</div>
                <div id="check-in-time" class="text-2xl font-bold text-gray-800">--:--:--</div>
                <div id="check-in-status" class="text-sm text-gray-500 mt-2">Chưa chấm công</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-sm text-gray-600 mb-2">Khoảng cách văn phòng</div>
                <div id="current-distance" class="text-2xl font-bold text-gray-800">--/60m</div>
                <div id="current-location" class="text-sm text-gray-500 mt-2">Đang xác định GPS...</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-sm text-gray-600 mb-2">Chấm công ra</div>
                <div id="check-out-time" class="text-2xl font-bold text-gray-800">--:--:--</div>
                <div id="check-out-status" class="text-sm text-gray-500 mt-2">Chưa chấm công</div>
            </div>
        </div>

        <div id="distance-alert" class="mb-6 hidden">
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                <p class="text-sm text-yellow-800" id="distance-alert-message">Bạn đang ngoài phạm vi 60m.</p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">Xác thực khuôn mặt</h2>
                    <p class="text-sm text-gray-600 mt-1">Nhìn thẳng camera, đủ sáng, chỉ một người trong khung hình. Ảnh sẽ gửi {{ $approverLabel }} duyệt trước khi dùng để chấm công.</p>
                </div>
                <div class="flex items-center gap-3">
                    <img id="registered-face" class="w-14 h-14 rounded-full object-cover border hidden" alt="Khuôn mặt đã đăng ký">
                    <span id="face-registration-status" class="text-sm font-semibold text-blue-600">Đang kiểm tra đăng ký...</span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
                <div class="rounded-lg border border-gray-200 overflow-hidden bg-black relative">
                    <video id="face-video" class="w-full h-72 object-cover" autoplay muted playsinline></video>
                    <canvas id="face-overlay" class="absolute inset-0 w-full h-72"></canvas>
                    <div id="face-guide" class="absolute inset-x-0 bottom-0 bg-black/50 text-white text-sm px-3 py-2">Đang mở camera...</div>
                </div>
                <div class="flex flex-col gap-3">
                    <label class="block text-sm font-medium text-gray-700">Ghi chú (tùy chọn)</label>
                    <textarea id="attendance-notes" class="w-full px-3 py-2 border border-gray-300 rounded-md" rows="3" placeholder="Ví dụ: Làm việc tại văn phòng"></textarea>
                    <button id="retry-camera-btn" type="button" class="w-full bg-slate-700 hover:bg-slate-800 text-white font-semibold py-3 px-4 rounded-lg">
                        Mở lại camera
                    </button>
                    <button id="register-face-btn" type="button" class="w-full bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-3 px-4 rounded-lg">
                        Gửi khuôn mặt cho {{ $approverLabel }} duyệt
                    </button>
                    <button id="punch-face-btn" type="button" class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-3 px-4 rounded-lg">
                        Chấm công
                    </button>
                    <div id="face-status-message" class="text-sm text-gray-700"></div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Lịch sử chấm công</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2 text-left">Ngày</th>
                            <th class="px-4 py-2 text-left">Chấm vào</th>
                            <th class="px-4 py-2 text-left">Chấm ra</th>
                            <th class="px-4 py-2 text-left">Trạng thái</th>
                            <th class="px-4 py-2 text-left">Vị trí vào</th>
                            <th class="px-4 py-2 text-left"></th>
                        </tr>
                    </thead>
                    <tbody id="history-table">
                        <tr>
                            <td class="px-4 py-2 text-center" colspan="6">Đang tải...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<dialog id="adjust-dialog" class="rounded-xl p-0 w-full max-w-md shadow-xl">
    <form method="POST" id="adjust-form" class="p-5">
        @csrf
        <h3 class="text-lg font-bold mb-1">Yêu cầu điều chỉnh chấm công</h3>
        <p class="text-sm text-gray-500 mb-4" id="adjust-date-label"></p>
        <div class="mb-3">
            <label class="block text-sm font-semibold mb-1">Giờ vào đề nghị</label>
            <input type="time" name="requested_check_in" class="w-full border rounded-lg px-3 py-2">
        </div>
        <div class="mb-3">
            <label class="block text-sm font-semibold mb-1">Giờ ra đề nghị</label>
            <input type="time" name="requested_check_out" class="w-full border rounded-lg px-3 py-2">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-semibold mb-1">Lý do</label>
            <textarea name="reason" required rows="3" class="w-full border rounded-lg px-3 py-2" placeholder="Ví dụ: Quên chấm công"></textarea>
        </div>
        <div class="flex gap-2 justify-end">
            <button type="button" class="px-4 py-2 rounded-lg border" onclick="document.getElementById('adjust-dialog').close()">Đóng</button>
            <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white font-semibold">Gửi HR</button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
<script src="/vendor/leaflet/leaflet.js"></script>
@vite('resources/js/employee-attendance.js')
@endpush
