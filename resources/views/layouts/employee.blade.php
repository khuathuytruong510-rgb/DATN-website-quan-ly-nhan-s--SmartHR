<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Chấm Công') - SmartHR</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { --primary: #2563eb; --danger: #dc2626; --sidebar-bg: #1e3a5f; --line: #e5e7eb; }
        body { margin: 0; background: #f8fafc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .emp-shell { display: flex; min-height: 100vh; }
        .emp-sidebar {
            width: 260px; min-width: 260px; background: var(--sidebar-bg); color: #cbd5e1;
            position: fixed; top: 0; left: 0; bottom: 0; z-index: 1040;
            display: flex; flex-direction: column; overflow-y: auto;
        }
        .emp-sidebar-brand { padding: 24px 20px 16px; border-bottom: 1px solid rgba(255,255,255,.1); }
        .emp-sidebar-brand h2 { margin: 0; font-size: 24px; font-weight: 800; color: #fff; }
        .emp-sidebar-brand p { margin: 4px 0 0; font-size: 12px; color: #94a3b8; }
        .emp-sidebar-nav { flex: 1; padding: 12px 10px; }
        .emp-sidebar-nav .nav-link {
            display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 8px;
            color: #cbd5e1; font-size: 14px; font-weight: 500; transition: background .15s, color .15s;
        }
        .emp-sidebar-nav .nav-link:hover { background: rgba(255,255,255,.08); color: #fff; }
        .emp-sidebar-nav .nav-link.active { background: var(--primary); color: #fff; }
        .emp-sidebar-nav .nav-link i { font-size: 16px; width: 20px; text-align: center; }
        .emp-main { flex: 1; margin-left: 260px; }
        .emp-topbar {
            background: #fff; border-bottom: 1px solid var(--line);
            padding: 16px 28px; display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 1030;
        }
        .emp-content { padding: 24px 28px; }
        @media (max-width: 991.98px) {
            .emp-sidebar { transform: translateX(-100%); }
            .emp-sidebar.show { transform: translateX(0); }
            .emp-main { margin-left: 0; }
        }
    </style>
</head>
<body>
    <div class="emp-shell">
        <aside class="emp-sidebar" id="empSidebar">
            <div class="emp-sidebar-brand">
                <h2>SmartHR</h2>
                <p>Cổng nhân viên</p>
            </div>
            <nav class="emp-sidebar-nav">
                <a class="nav-link {{ request()->routeIs('me.dashboard') ? 'active' : '' }}" href="{{ route('me.dashboard') }}"><i class="bi bi-house-fill"></i> Dashboard</a>
                <a class="nav-link {{ request()->routeIs('me.profile*') ? 'active' : '' }}" href="{{ route('me.profile') }}"><i class="bi bi-person-fill"></i> Hồ sơ</a>
                <a class="nav-link {{ request()->routeIs('me.attendance*') ? 'active' : '' }}" href="{{ route('me.attendance') }}"><i class="bi bi-geo-alt-fill"></i> Chấm công</a>
                <a class="nav-link {{ request()->routeIs('me.leave_requests*') ? 'active' : '' }}" href="{{ route('me.leave_requests') }}"><i class="bi bi-journal-text"></i> Đơn xin nghỉ</a>
                <a class="nav-link {{ request()->routeIs('me.overtime_requests*') ? 'active' : '' }}" href="{{ route('me.overtime_requests') }}"><i class="bi bi-clock"></i> Đăng ký tăng ca</a>
                <a class="nav-link {{ request()->routeIs('me.payrolls') ? 'active' : '' }}" href="{{ route('me.payrolls') }}"><i class="bi bi-cash-stack"></i> Bảng lương</a>
                <a class="nav-link {{ request()->routeIs('me.contracts') ? 'active' : '' }}" href="{{ route('me.contracts') }}"><i class="bi bi-file-earmark-text"></i> Hợp đồng</a>
                <a class="nav-link {{ request()->routeIs('me.evaluations') ? 'active' : '' }}" href="{{ route('me.evaluations') }}"><i class="bi bi-star-fill"></i> Đánh giá</a>
                <a class="nav-link {{ request()->routeIs('me.benefits') ? 'active' : '' }}" href="{{ route('me.benefits') }}"><i class="bi bi-gift-fill"></i> Phúc lợi</a>
                <a class="nav-link {{ request()->routeIs('me.notifications') ? 'active' : '' }}" href="{{ route('me.notifications') }}"><i class="bi bi-bell-fill"></i> Thông báo</a>
                <a class="nav-link {{ request()->routeIs('me.schedule*') ? 'active' : '' }}" href="{{ route('me.schedule') }}"><i class="bi bi-calendar-week"></i> Lịch làm việc</a>
                <a class="nav-link {{ request()->routeIs('me.support_requests*') ? 'active' : '' }}" href="{{ route('me.support_requests') }}"><i class="bi bi-ticket-detailed"></i> Yêu cầu hỗ trợ</a>
                <a class="nav-link {{ request()->routeIs('me.password.*') ? 'active' : '' }}" href="{{ route('me.password.change') }}"><i class="bi bi-lock-fill"></i> Đổi mật khẩu</a>
                <a class="nav-link {{ request()->routeIs('me.activity_logs') ? 'active' : '' }}" href="{{ route('me.activity_logs') }}"><i class="bi bi-journal-text"></i> Nhật ký hoạt động</a>
            </nav>
        </aside>

        <main class="emp-main">
            <div class="emp-topbar">
                <div>
                    <button class="btn d-lg-none" type="button" onclick="document.getElementById('empSidebar').classList.toggle('show')" style="border:none;background:transparent;font-size:24px;padding:4px 8px;">
                        <i class="bi bi-list"></i>
                    </button>
                    <strong>{{ Auth::user()->name }}</strong>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-box-arrow-right me-1"></i> Đăng xuất</button>
                </form>
            </div>
            <div class="emp-content">
                @hasSection('breadcrumb')
                    <nav aria-label="breadcrumb" style="margin-bottom:16px;">
                        <ol class="breadcrumb" style="background:transparent;padding:0;margin:0;font-size:14px;">
                            @yield('breadcrumb')
                        </ol>
                    </nav>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show">
                        <ul style="margin:0;padding-left:18px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
