<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Chấm Công') - SmartHR</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                        <a href="{{ route('me.attendance') }}" class="flex items-center px-4 py-2 rounded-lg {{ request()->routeIs('me.attendance*') ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}">
                            <span class="mr-3">📍</span> Chấm Công
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-100">
                            <span class="mr-3">📊</span> Thống Kê
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-100">
                            <span class="mr-3">📋</span> Lịch Sử
                        </a>
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

