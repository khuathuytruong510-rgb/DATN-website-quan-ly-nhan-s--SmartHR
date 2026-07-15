<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Chấm Công') - SmartHR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body class="bg-gray-50">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-md h-screen fixed">
            <div class="p-6 border-b">
                <h1 class="text-2xl font-bold text-blue-600">SmartHR</h1>
                <p class="text-sm text-gray-600 mt-1">Quản lý nhân sự</p>
            </div>
            <nav class="mt-6 px-4">
                <ul class="space-y-2">
                    <li>
                        <a href="{{ route('me.dashboard') }}" class="flex items-center px-4 py-2 rounded-lg {{ request()->routeIs('me.dashboard') ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}"><i class="bi bi-house me-2"></i> Dashboard</a>
                    </li>
                    <li>
                        <a href="{{ route('me.profile') }}" class="flex items-center px-4 py-2 rounded-lg {{ request()->routeIs('me.profile*') ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}"><i class="bi bi-person me-2"></i> Hồ sơ</a>
                    </li>
                    <li>
                        <a href="{{ route('me.attendance') }}" class="flex items-center px-4 py-2 rounded-lg {{ request()->routeIs('me.attendance*') ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}"><i class="bi bi-geo-alt me-2"></i> Chấm công</a>
                    </li>
                    <li>
                        <a href="{{ route('me.attendance') }}" class="flex items-center px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-100"><i class="bi bi-calendar3 me-2"></i> Lịch sử chấm công</a>
                    </li>
                    <li>
                        <a href="{{ route('me.leave_requests') }}" class="flex items-center px-4 py-2 rounded-lg {{ request()->routeIs('me.leave_requests*') ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}"><i class="bi bi-journal-text me-2"></i> Đơn xin nghỉ</a>
                    </li>
                    <li>
                        <a href="{{ route('me.overtime_requests') }}" class="flex items-center px-4 py-2 rounded-lg {{ request()->routeIs('me.overtime_requests*') ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}"><i class="bi bi-clock me-2"></i> Đăng ký tăng ca</a>
                    </li>
                    <li>
                        <a href="{{ route('me.payrolls') }}" class="flex items-center px-4 py-2 rounded-lg {{ request()->routeIs('me.payrolls') ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}"><i class="bi bi-cash-stack me-2"></i> Bảng lương</a>
                    </li>
                    <li>
                        <a href="{{ route('me.contracts') }}" class="flex items-center px-4 py-2 rounded-lg {{ request()->routeIs('me.contracts') ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}"><i class="bi bi-file-earmark-text me-2"></i> Hợp đồng</a>
                    </li>
                    <li>
                        <a href="{{ route('me.evaluations') }}" class="flex items-center px-4 py-2 rounded-lg {{ request()->routeIs('me.evaluations') ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}"><i class="bi bi-star me-2"></i> Đánh giá</a>
                    </li>
                    <li>
                        <a href="{{ route('me.benefits') }}" class="flex items-center px-4 py-2 rounded-lg {{ request()->routeIs('me.benefits') ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}"><i class="bi bi-gift me-2"></i> Phúc lợi</a>
                    </li>
                    <li>
                        <a href="{{ route('me.notifications') }}" class="flex items-center px-4 py-2 rounded-lg {{ request()->routeIs('me.notifications') ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}"><i class="bi bi-bell me-2"></i> Thông báo</a>
                    </li>
                    <li>
                        <a href="{{ route('me.schedule') }}" class="flex items-center px-4 py-2 rounded-lg {{ request()->routeIs('me.schedule') ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}"><i class="bi bi-calendar-week me-2"></i> Lịch làm việc</a>
                    </li>
                    <li>
                        <a href="{{ route('me.support_requests') }}" class="flex items-center px-4 py-2 rounded-lg {{ request()->routeIs('me.support_requests*') ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}"><i class="bi bi-ticket-detailed me-2"></i> Yêu cầu hỗ trợ</a>
                    </li>
                    <li>
                        <a href="{{ route('me.password.change') }}" class="flex items-center px-4 py-2 rounded-lg {{ request()->routeIs('me.password.*') ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}"><i class="bi bi-lock me-2"></i> Đổi mật khẩu</a>
                    </li>
                    <li>
                        <a href="{{ route('me.activity_logs') }}" class="flex items-center px-4 py-2 rounded-lg {{ request()->routeIs('me.activity_logs') ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}"><i class="bi bi-journal-text me-2"></i> Nhật ký hoạt động</a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="ml-64 flex-1">
            <!-- Top Bar -->
            <div class="bg-white shadow">
                <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">SmartHR Dashboard</h2>
                        <p class="text-sm text-gray-600">Hệ thống quản lý nhân sự</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-700"><strong>{{ Auth::user()->name }}</strong></span>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                                Đăng xuất
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <div class="p-6">
                @hasSection('breadcrumb')
                    <nav aria-label="breadcrumb" style="margin-bottom:12px;">
                        <ol style="list-style:none; padding:0; margin:0; display:flex; gap:8px; align-items:center; font-size:14px; color:#64748b;">
                            @yield('breadcrumb')
                        </ol>
                    </nav>
                @endif

                @if ($errors->any())
                    <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded">
                        <h3 class="text-red-800 font-semibold">Lỗi</h3>
                        <ul class="text-red-700 text-sm mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('success'))
                    <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4 rounded">
                        <p class="text-green-800">{{ session('success') }}</p>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded">
                        <p class="text-red-800">{{ session('error') }}</p>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>

