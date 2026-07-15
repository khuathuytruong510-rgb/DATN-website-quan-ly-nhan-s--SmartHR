<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SmartHR')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        :root {
            --bg: #eef3ff;
            --sidebar: #111827;
            --sidebar-soft: #1f2937;
            --panel: #fff;
            --line: #e5e7eb;
            --text: #111827;
            --muted: #64748b;
            --primary: #2563eb;
            --danger: #dc2626;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: var(--bg); color: var(--text); }
        a { color: inherit; }
        .auth-page { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .auth-card { width: min(440px, 100%); background: var(--panel); border-radius: 12px; padding: 28px; box-shadow: 0 20px 60px rgba(15, 23, 42, .12); }
        .auth-card h1 { margin: 0 0 8px; font-size: 28px; }
        .shell { min-height: 100vh; display: grid; grid-template-columns: 260px 1fr; }
        .sidebar { background: var(--sidebar); color: #e5e7eb; padding: 28px 22px; }
        .brand { font-size: 28px; font-weight: 800; margin-bottom: 6px; }
        .brand-subtitle { color: #cbd5e1; margin: 0 0 28px; }
        .nav { display: grid; gap: 10px; }
        .nav a { text-decoration: none; padding: 13px 16px; border-radius: 8px; color: #e5e7eb; }
        .nav-group { display: block; }
        .nav-group summary { list-style: none; cursor: pointer; margin: 0; }
        .nav-group summary::-webkit-details-marker { display:none; }
        .nav-summary { display: block; padding: 13px 16px; border-radius: 8px; color: #e5e7eb; }
        .nav-group a { display: block; padding: 10px 24px; margin-left: 4px; border-radius: 6px; color: #e5e7eb; text-decoration: none; }
        .nav-group[open] .nav-summary, .nav-summary.active { background: var(--sidebar-soft); }
        .nav a.active, .nav a:hover { background: var(--sidebar-soft); }
        .main { min-width: 0; }
        .topbar { background: var(--panel); border-bottom: 1px solid var(--line); padding: 24px 30px; display: flex; justify-content: space-between; align-items: center; gap: 16px; }
        .topbar-title { font-weight: 800; font-size: 20px; }
        .userbox { display: flex; align-items: center; gap: 12px; }
        .content { padding: 30px; }
        .page-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 22px; }
        h1 { margin: 0 0 8px; font-size: 32px; }
        .muted { color: var(--muted); margin: 0; }
        .grid { display: grid; gap: 20px; }
        .stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .two-cols { grid-template-columns: 1fr 1fr; }
        .card { background: var(--panel); border: 1px solid var(--line); border-radius: 12px; padding: 24px; box-shadow: 0 10px 40px rgba(15, 23, 42, .06); }
        .stat-value { font-size: 40px; font-weight: 800; margin: 18px 0 10px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 8px; padding: 10px 14px; font-weight: 700; text-decoration: none; cursor: pointer; background: #f8fafc; color: var(--text); }
        .btn.primary { background: var(--primary); color: #fff; }
        .btn.danger { background: #fee2e2; color: var(--danger); }
        .btn.link { background: transparent; color: var(--primary); padding-left: 0; }
        .actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 14px 10px; border-bottom: 1px solid var(--line); vertical-align: top; }
        th { color: var(--muted); font-size: 13px; text-transform: uppercase; }
        .field { display: grid; gap: 7px; margin-bottom: 16px; }
        label { font-weight: 700; }
        input, select, textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 11px 12px; font: inherit; background: #fff; }
        textarea { min-height: 110px; resize: vertical; }
        .error { color: var(--danger); font-size: 13px; }
        .alert { border-radius: 8px; padding: 13px 14px; margin-bottom: 16px; background: #dcfce7; color: #166534; }
        .badge { display: inline-flex; border-radius: 999px; padding: 5px 9px; font-size: 12px; font-weight: 700; background: #e0f2fe; color: #0369a1; }
        .badge.inactive, .badge.expired { background: #fee2e2; color: var(--danger); }
        .badge.pending { background: #fef3c7; color: #92400e; }
        .empty { background: #f8fafc; padding: 18px; border-radius: 8px; color: var(--muted); }
        .pagination { margin-top: 18px; }
        @media (max-width: 900px) {
            .shell { grid-template-columns: 1fr; }
            .stats, .two-cols { grid-template-columns: 1fr; }
            .topbar, .page-head { flex-direction: column; align-items: stretch; }
        }
    </style>
</head>
<body>
    @auth
        <div class="shell">
            <aside class="sidebar">
                <div class="brand">SmartHR</div>
                <p class="brand-subtitle">Quản lý nhân sự</p>
                <nav class="nav">
                    @php $user = auth()->user(); @endphp
                    @if ($user->is_admin || $user->is_hr || $user->is_accountant)
                        <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
                        <a class="{{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}">Nhân viên</a>
                        <a class="{{ request()->routeIs('departments.*') ? 'active' : '' }}" href="{{ route('departments.index') }}">Phòng ban</a>
                        <a class="{{ request()->routeIs('positions.*') ? 'active' : '' }}" href="{{ route('positions.index') }}">Chức vụ</a>
                        <a class="{{ request()->routeIs('contracts.*') ? 'active' : '' }}" href="{{ route('contracts.index') }}">Hợp đồng</a>
                        <a class="{{ request()->routeIs('attendance.*') ? 'active' : '' }}" href="{{ route('attendance.index') }}">Chấm công</a>
                        <a class="{{ request()->routeIs('evaluations.*') ? 'active' : '' }}" href="{{ route('evaluations.index') }}">Đánh giá</a>
                        <a class="{{ request()->routeIs('leave_requests.*') ? 'active' : '' }}" href="{{ route('leave_requests.index') }}">Nghỉ phép</a>
                        @php
                            $payrollActive = request()->routeIs('payroll.*') || request()->routeIs('salary_histories.*') || request()->routeIs('payroll.email.*');
                        @endphp
                        <details class="nav-group" {{ $payrollActive ? 'open' : '' }}>
                            <summary class="nav-summary {{ $payrollActive ? 'active' : '' }}">Lương</summary>
                            <a class="{{ request()->routeIs('payroll.index') ? 'active' : '' }}" href="{{ route('payroll.index') }}">Tính lương</a>
                            <a class="{{ request()->routeIs('salary_histories.index') ? 'active' : '' }}" href="{{ route('salary_histories.index') }}">Lịch sử lương</a>
                            <a class="{{ request()->routeIs('payroll.email.*') ? 'active' : '' }}" href="{{ route('payroll.email.index') }}">Gửi phiếu lương</a>
                        </details>
                        <a class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}">Thông báo</a>
                        @if ($user->is_admin)
                            <a class="{{ request()->routeIs('accounts.*') ? 'active' : '' }}" href="{{ route('accounts.index') }}">Quản lý tài khoản</a>
                            <a class="{{ request()->routeIs('permissions.*') ? 'active' : '' }}" href="{{ route('permissions.index') }}">Phân quyền</a>
                            <a class="{{ request()->routeIs('system_logs.*') ? 'active' : '' }}" href="{{ route('system_logs.index') }}">Nhật ký hệ thống</a>
                            <a class="{{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">Cấu hình hệ thống</a>
                        @endif
                        @if ($user->is_hr)
                            <a class="{{ request()->routeIs('recruitment.*') ? 'active' : '' }}" href="#">Tuyển dụng</a>
                            <a class="{{ request()->routeIs('training.*') ? 'active' : '' }}" href="#">Đào tạo</a>
                            <a class="{{ request()->routeIs('benefits.*') ? 'active' : '' }}" href="{{ route('benefits.index') }}">Phúc lợi</a>
                        @endif
                        @if ($user->is_accountant)
                            <details class="nav-group" {{ request()->routeIs('accountant.*') ? 'open' : '' }}>
                                <summary class="nav-summary {{ request()->routeIs('accountant.*') ? 'active' : '' }}">Kế toán</summary>
                                <a class="{{ request()->routeIs('accountant.dashboard') ? 'active' : '' }}" href="{{ route('accountant.dashboard') }}">Dashboard</a>
                                <a class="{{ request()->routeIs('accountant.payroll.*') ? 'active' : '' }}" href="{{ route('accountant.payroll.index') }}">Quản lý bảng lương</a>
                                <a class="{{ request()->routeIs('accountant.payroll.generate') ? 'active' : '' }}" href="{{ route('accountant.payroll.generate') }}">Tính lương</a>
                                <a class="{{ request()->routeIs('accountant.payroll.send') ? 'active' : '' }}" href="{{ route('accountant.payroll.send') }}">Gửi bảng lương</a>
                                <a class="{{ request()->routeIs('accountant.payroll.feedback') ? 'active' : '' }}" href="{{ route('accountant.payroll.feedback') }}">Phản hồi lương</a>
                                <a class="{{ request()->routeIs('accountant.allowances') ? 'active' : '' }}" href="{{ route('accountant.allowances') }}">Quản lý phụ cấp</a>
                                <a class="{{ request()->routeIs('accountant.deductions') ? 'active' : '' }}" href="{{ route('accountant.deductions') }}">Quản lý khấu trừ</a>
                                <a class="{{ request()->routeIs('accountant.bonuses') ? 'active' : '' }}" href="{{ route('accountant.bonuses') }}">Quản lý thưởng</a>
                                <a class="{{ request()->routeIs('accountant.reports') ? 'active' : '' }}" href="{{ route('accountant.reports') }}">Báo cáo lương</a>
                                <a class="{{ request()->routeIs('accountant.export') ? 'active' : '' }}" href="{{ route('accountant.export') }}">Xuất PDF/Excel</a>
                                <a class="{{ request()->routeIs('accountant.activity_logs') ? 'active' : '' }}" href="{{ route('accountant.activity_logs') }}">Nhật ký hoạt động</a>
                                <a class="{{ request()->routeIs('accountant.profile') ? 'active' : '' }}" href="{{ route('accountant.profile') }}">Hồ sơ cá nhân</a>
                                <a class="{{ request()->routeIs('accountant.password.*') ? 'active' : '' }}" href="{{ route('accountant.password.change') }}">Đổi mật khẩu</a>
                            </details>
                        @endif
                    @else
                        <a class="{{ request()->routeIs('me.dashboard') ? 'active' : '' }}" href="{{ route('me.dashboard') }}"><i class="bi bi-house me-2"></i>Dashboard</a>
                        <a class="{{ request()->routeIs('me.profile') || request()->routeIs('me.profile.*') ? 'active' : '' }}" href="{{ route('me.profile') }}"><i class="bi bi-person me-2"></i>Hồ sơ</a>
                        <a class="{{ request()->routeIs('me.attendance') || request()->routeIs('me.attendance.*') ? 'active' : '' }}" href="{{ route('me.attendance') }}"><i class="bi bi-geo-alt me-2"></i>Chấm công</a>
                        <a class="{{ request()->routeIs('me.attendance.history') || request()->routeIs('me.attendance.*history') ? 'active' : '' }}" href="{{ route('me.attendance') }}"><i class="bi bi-calendar3 me-2"></i>Lịch sử chấm công</a>
                        <a class="{{ request()->routeIs('me.leave_requests') || request()->routeIs('me.leave_requests.*') ? 'active' : '' }}" href="{{ route('me.leave_requests') }}"><i class="bi bi-journal-text me-2"></i>Đơn xin nghỉ</a>
                        <a class="{{ request()->routeIs('me.overtime_requests') || request()->routeIs('me.overtime_requests.*') ? 'active' : '' }}" href="{{ route('me.overtime_requests') }}"><i class="bi bi-clock me-2"></i>Đăng ký tăng ca</a>
                        @php
                            $mePayrollActive = request()->routeIs('me.payrolls') || request()->routeIs('me.salary_histories');
                        @endphp
                        <details class="nav-group" {{ $mePayrollActive ? 'open' : '' }}>
                            <summary class="nav-summary {{ $mePayrollActive ? 'active' : '' }}">Lương</summary>
                            <a class="{{ request()->routeIs('me.payrolls') ? 'active' : '' }}" href="{{ route('me.payrolls') }}">Tính lương</a>
                            <a class="{{ request()->routeIs('me.salary_histories') ? 'active' : '' }}" href="{{ route('me.salary_histories') }}">Lịch sử lương</a>
                        </details>
                        <a class="{{ request()->routeIs('me.contracts') ? 'active' : '' }}" href="{{ route('me.contracts') }}"><i class="bi bi-file-earmark-text me-2"></i>Hợp đồng</a>
                        <a class="{{ request()->routeIs('me.payrolls') ? 'active' : '' }}" href="{{ route('me.payrolls') }}"><i class="bi bi-cash-stack me-2"></i>Bảng lương</a>
                        <a class="{{ request()->routeIs('me.payrolls') ? 'active' : '' }}" href="{{ route('me.payrolls') }}"><i class="bi bi-check2-square me-2"></i>Xác nhận bảng lương</a>
                        <a class="{{ request()->routeIs('me.evaluations') ? 'active' : '' }}" href="{{ route('me.evaluations') }}"><i class="bi bi-star me-2"></i>Đánh giá</a>
                        <a class="{{ request()->routeIs('me.benefits') ? 'active' : '' }}" href="{{ route('me.benefits') }}"><i class="bi bi-gift me-2"></i>Phúc lợi</a>
                        <a class="{{ request()->routeIs('me.notifications') ? 'active' : '' }}" href="{{ route('me.notifications') }}"><i class="bi bi-bell me-2"></i>Thông báo</a>
                        <a class="{{ request()->routeIs('me.schedule') || request()->routeIs('me.schedule.*') ? 'active' : '' }}" href="{{ route('me.schedule') }}"><i class="bi bi-calendar-week me-2"></i>Lịch làm việc</a>
                        <a class="{{ request()->routeIs('me.support_requests*') ? 'active' : '' }}" href="{{ route('me.support_requests') }}"><i class="bi bi-ticket-detailed me-2"></i>Yêu cầu hỗ trợ</a>
                        <a class="{{ request()->routeIs('me.password.*') || request()->routeIs('me.password.change') ? 'active' : '' }}" href="{{ route('me.password.change') }}"><i class="bi bi-lock me-2"></i>Đổi mật khẩu</a>
                        <a class="{{ request()->routeIs('me.activity_logs') ? 'active' : '' }}" href="{{ route('me.activity_logs') }}"><i class="bi bi-journal-text me-2"></i>Nhật ký hoạt động</a>
                    @endif
                </nav>
            </aside>
            <main class="main">
                <header class="topbar">
                    <div>
                        <div class="topbar-title">SmartHR Dashboard</div>
                        <p class="muted">Hệ thống quản lý nhân sự</p>
                    </div>
                    <div class="userbox">
                        <strong>{{ auth()->user()->name }}</strong>
                        @if(session()->has('impersonator_id'))
                            <form method="POST" action="{{ route('impersonation.stop') }}">
                                @csrf
                                <button class="btn" type="submit">Quay lại admin</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn" type="submit">Đăng xuất</button>
                        </form>
                    </div>
                </header>
                <section id="app" class="content">
                    @hasSection('breadcrumb')
                        <nav aria-label="breadcrumb" style="margin-bottom:12px;">
                            <ol style="list-style:none; padding:0; margin:0; display:flex; gap:8px; align-items:center; font-size:14px; color:var(--muted);">
                                @yield('breadcrumb')
                            </ol>
                        </nav>
                    @endif
                    @if (session('success'))
                        <alert type="success">{{ session('success') }}</alert>
                    @endif
                    @if (session('error'))
                        <alert type="error">{{ session('error') }}</alert>
                    @endif
                    @yield('content')
                </section>
            </main>
        </div>
    @else
        <main class="auth-page">
            @yield('content')
        </main>
    @endauth
    @stack('scripts')
</body>
</html>
