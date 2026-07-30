<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SmartHR')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style>
        :root {
            --bg: #eef3ff;
            --sidebar: #111827;
            --sidebar-soft: #1f2937;
            --sidebar-active: #2563eb;
            --panel: #fff;
            --line: #e5e7eb;
            --text: #111827;
            --muted: #64748b;
            --primary: #2563eb;
            --danger: #dc2626;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: var(--bg); color: var(--text); }
        a { color: inherit; text-decoration: none; }

        .auth-page { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .auth-card { width: min(440px, 100%); background: var(--panel); border-radius: 12px; padding: 28px; box-shadow: 0 20px 60px rgba(15, 23, 42, .12); }
        .auth-card h1 { margin: 0 0 8px; font-size: 28px; }

        .shell { min-height: 100vh; display: flex; }

        .sidebar {
            width: 270px;
            min-width: 270px;
            background: var(--sidebar);
            color: #cbd5e1;
            padding: 0;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 1040;
            overflow-y: auto;
            transition: transform .3s ease;
        }
        .sidebar-brand {
            padding: 24px 20px 16px;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .sidebar-brand h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            color: #fff;
        }
        .sidebar-brand p {
            margin: 4px 0 0;
            font-size: 12px;
            color: #94a3b8;
        }
        .sidebar-nav {
            flex: 1;
            padding: 12px 10px;
            overflow-y: auto;
        }
        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 8px;
            color: #cbd5e1;
            font-size: 14px;
            font-weight: 500;
            transition: background .15s, color .15s;
            white-space: nowrap;
        }
        .sidebar-nav .nav-link:hover {
            background: var(--sidebar-soft);
            color: #fff;
        }
        .sidebar-nav .nav-link.active {
            background: var(--sidebar-active);
            color: #fff;
        }
        .sidebar-nav .nav-link i {
            font-size: 16px;
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }
        .sidebar-nav .nav-item { margin-bottom: 2px; }
        .sidebar-nav .nav-item .accordion-button {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 8px;
            color: #cbd5e1;
            font-size: 14px;
            font-weight: 500;
            background: transparent;
            border: none;
            width: 100%;
            text-align: left;
            box-shadow: none;
            transition: background .15s, color .15s;
        }
        .sidebar-nav .nav-item .accordion-button:not(.collapsed) {
            background: var(--sidebar-soft);
            color: #fff;
        }
        .sidebar-nav .nav-item .accordion-button:hover {
            background: var(--sidebar-soft);
            color: #fff;
        }
        .sidebar-nav .nav-item .accordion-button i {
            font-size: 16px;
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }
        .sidebar-nav .nav-item .accordion-button .chevron {
            margin-left: auto;
            transition: transform .2s;
        }
        .sidebar-nav .nav-item .accordion-button:not(.collapsed) .chevron {
            transform: rotate(90deg);
        }
        .sidebar-nav .nav-item .accordion-body {
            padding: 0 0 0 20px;
        }
        .sidebar-nav .nav-item .accordion-body .sub-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 6px;
            color: #94a3b8;
            font-size: 13px;
            font-weight: 500;
            transition: background .15s, color .15s;
        }
        .sidebar-nav .nav-item .accordion-body .sub-link:hover {
            background: rgba(255,255,255,.06);
            color: #e2e8f0;
        }
        .sidebar-nav .nav-item .accordion-body .sub-link.active {
            background: rgba(37,99,235,.2);
            color: #60a5fa;
        }
        .sidebar-section-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #475569;
            padding: 16px 14px 6px;
        }

        .main-content {
            flex: 1;
            margin-left: 270px;
            min-width: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .topbar {
            background: var(--panel);
            border-bottom: 1px solid var(--line);
            padding: 16px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 1030;
        }
        .topbar-title { font-weight: 800; font-size: 20px; }
        .userbox { display: flex; align-items: center; gap: 12px; }
        .content { padding: 14px 28px 22px; min-height: 0; flex: 1; display: flex; flex-direction: column; }
        .page-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 16px; }
        h1 { margin: 0 0 8px; font-size: 32px; }
        .muted { color: var(--muted); margin: 0; }
        .grid { display: grid; gap: 20px; }
        .stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .two-cols { grid-template-columns: 1fr 1fr; }
        .card { background: var(--panel); border: 1px solid var(--line); border-radius: 12px; padding: 24px; box-shadow: 0 10px 40px rgba(15, 23, 42, .06); }
        .contract-page { display: flex; flex-direction: column; gap: 14px; }
        .contract-page .page-head { margin-bottom: 16px; }
        .contract-page .card { padding: 16px 18px; box-shadow: 0 8px 24px rgba(15, 23, 42, .05); }
        .contract-page .card-header { padding: 0 0 10px; margin-bottom: 10px; border-bottom: 1px solid var(--line); }
        .contract-page .card-body { padding: 0.75rem 0 0; }
        .contract-page .container-fluid { padding: 0; }
        .stat-value { font-size: 40px; font-weight: 800; margin: 18px 0 10px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 8px; padding: 10px 14px; font-weight: 700; text-decoration: none; cursor: pointer; background: #f8fafc; color: var(--text); border: 1px solid var(--line); transition: background .15s; }
        .btn:hover { background: #f1f5f9; }
        .btn.primary { background: var(--primary); color: #fff; border-color: var(--primary); }
        .btn.primary:hover { background: #1d4ed8; }
        .btn.danger { background: #fee2e2; color: var(--danger); border-color: #fecaca; }
        .btn.danger:hover { background: #fecaca; }
        .btn.success { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
        .btn.success:hover { background: #bbf7d0; }
        .btn.warning { background: #fef3c7; color: #92400e; border-color: #fde68a; }
        .btn.warning:hover { background: #fde68a; }
        .btn.link { background: transparent; color: var(--primary); padding-left: 0; border: none; }
        .actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 14px 10px; border-bottom: 1px solid var(--line); vertical-align: top; }
        th { color: var(--muted); font-size: 13px; text-transform: uppercase; }
        .field { display: grid; gap: 7px; margin-bottom: 16px; }
        label { font-weight: 700; }
        input, select, textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 11px 12px; font: inherit; background: #fff; }
        input:focus, select:focus, textarea:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(37,99,235,.15); }
        textarea { min-height: 110px; resize: vertical; }
        .error { color: var(--danger); font-size: 13px; }
        .alert { border-radius: 8px; padding: 13px 14px; margin-bottom: 16px; background: #dcfce7; color: #166534; }
        .alert-danger { background: #fee2e2; color: #991b1b; }
        .badge { display: inline-flex; border-radius: 999px; padding: 5px 9px; font-size: 12px; font-weight: 700; background: #e0f2fe; color: #0369a1; }
        .badge.inactive, .badge.expired { background: #fee2e2; color: var(--danger); }
        .badge.pending { background: #fef3c7; color: #92400e; }
        .badge.approved { background: #d1fae5; color: #065f46; }
        .badge.rejected { background: #fee2e2; color: var(--danger); }
        .empty { background: #f8fafc; padding: 18px; border-radius: 8px; color: var(--muted); }
        .pagination { margin-top: 18px; }

        /* Override Tailwind's .collapse utility that conflicts with Bootstrap accordion */
        .accordion-collapse {
            visibility: visible !important;
        }
        .accordion-collapse:not(.show) {
            display: none !important;
        }
        .accordion-collapse.show {
            display: block !important;
        }

        .modal-content { border-radius: 14px; border: none; }
        .modal-header { border-bottom: 1px solid var(--line); padding: 18px 24px; }
        .modal-header .btn-close { padding: 0; margin: 0; }
        .modal-body { padding: 20px 24px; }
        .modal-footer { border-top: 1px solid var(--line); padding: 14px 24px; }

        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .stats, .two-cols { grid-template-columns: 1fr; }
            .topbar, .page-head { flex-direction: column; align-items: stretch; }
        }
    </style>
</head>
<body>
    @auth
        <div class="shell">
            <aside class="sidebar" id="appSidebar">
                <div class="sidebar-brand">
                    <h2>SmartHR</h2>
                    <p>Quản lý nhân sự</p>
                </div>
                <div class="sidebar-nav">
                    @php $user = auth()->user(); @endphp
                    @if ($user->is_admin || $user->is_hr)
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <i class="bi bi-grid-1x2-fill"></i> Dashboard
                        </a>
                        <a class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}">
                            <i class="bi bi-people-fill"></i> Nhân viên
                        </a>
                        <a class="nav-link {{ request()->routeIs('departments.*') ? 'active' : '' }}" href="{{ route('departments.index') }}">
                            <i class="bi bi-diagram-3-fill"></i> Phòng ban
                        </a>
                        <a class="nav-link {{ request()->routeIs('positions.*') ? 'active' : '' }}" href="{{ route('positions.index') }}">
                            <i class="bi bi-briefcase-fill"></i> Chức vụ
                        </a>
                        <a class="nav-link {{ request()->routeIs('contracts.*') ? 'active' : '' }}" href="{{ route('contracts.index') }}">
                            <i class="bi bi-file-earmark-text"></i> Hợp đồng
                        </a>
                        <a class="nav-link {{ request()->routeIs('attendance.*') ? 'active' : '' }}" href="{{ route('attendance.index') }}">
                            <i class="bi bi-clock-history"></i> Chấm công
                        </a>
                        <a class="nav-link {{ request()->routeIs('evaluations.*') ? 'active' : '' }}" href="{{ route('evaluations.index') }}">
                            <i class="bi bi-star-fill"></i> Đánh giá
                        </a>
                        <a class="nav-link {{ request()->routeIs('leave_requests.*') ? 'active' : '' }}" href="{{ route('leave_requests.index') }}">
                            <i class="bi bi-journal-text"></i> Nghỉ phép
                        </a>

                        @php
                            $payrollActive = request()->routeIs('payroll.*') || request()->routeIs('salary_histories.*') || request()->routeIs('payroll.email.*') || request()->routeIs('salary_payments.*') || request()->routeIs('payment_center.*') || request()->routeIs('statistics.*') || request()->routeIs('payroll.issues.*') || request()->routeIs('hr-dashboard.*');
                        @endphp
                        <div class="nav-item">
                            <div class="accordion" id="payrollAccordion">
                                <div class="accordion-item" style="background:transparent;border:none;">
                                    <button class="accordion-button {{ $payrollActive ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#payrollMenu" aria-expanded="{{ $payrollActive ? 'true' : 'false' }}">
                                        <i class="bi bi-cash-stack"></i> Lương
                                        <span class="chevron"><i class="bi bi-chevron-right"></i></span>
                                    </button>
                                    <div id="payrollMenu" class="accordion-collapse collapse {{ $payrollActive ? 'show' : '' }}" data-bs-parent="#payrollAccordion">
                                        <div class="accordion-body">
                                            <a class="sub-link {{ request()->routeIs('payroll.index') ? 'active' : '' }}" href="{{ route('payroll.index') }}">Tính lương</a>
                                            <a class="sub-link {{ request()->routeIs('payroll.email.*') ? 'active' : '' }}" href="{{ route('payroll.email.index') }}">Gửi phiếu lương</a>
                                            <a class="sub-link {{ request()->routeIs('salary_payments.*') ? 'active' : '' }}" href="{{ route('salary_payments.index') }}">Thanh toán</a>
                                            <a class="sub-link {{ request()->routeIs('salary_histories.*') ? 'active' : '' }}" href="{{ route('salary_histories.index') }}">Lịch sử lương</a>
                                            <a class="sub-link {{ request()->routeIs('payroll.issues.*') ? 'active' : '' }}" href="{{ route('payroll.issues.index') }}">Sự cố lương</a>
                                            <a class="sub-link {{ request()->routeIs('payment_center.*') ? 'active' : '' }}" href="{{ route('payment_center.dashboard') }}">Trung tâm thanh toán</a>
                                            <a class="sub-link {{ request()->routeIs('payment_center.history') ? 'active' : '' }}" href="{{ route('payment_center.history') }}">Lịch sử thanh toán</a>
                                            <a class="sub-link {{ request()->routeIs('statistics.*') ? 'active' : '' }}" href="{{ route('statistics.index') }}">Thống kê & Báo cáo</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <a class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}">
                            <i class="bi bi-bell-fill"></i> Thông báo
                        </a>

                        @if ($user->is_admin)
                            <div class="sidebar-section-label">Quản trị</div>
                            <a class="nav-link {{ request()->routeIs('accounts.*') ? 'active' : '' }}" href="{{ route('accounts.index') }}">
                                <i class="bi bi-person-gear"></i> Quản lý tài khoản
                            </a>
                            <a class="nav-link {{ request()->routeIs('permissions.*') ? 'active' : '' }}" href="{{ route('permissions.index') }}">
                                <i class="bi bi-shield-lock"></i> Phân quyền
                            </a>
                            <a class="nav-link {{ request()->routeIs('system_logs.*') ? 'active' : '' }}" href="{{ route('system_logs.index') }}">
                                <i class="bi bi-journal-code"></i> Nhật ký hệ thống
                            </a>
                            <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">
                                <i class="bi bi-gear-fill"></i> Cấu hình hệ thống
                            </a>
                        @endif

                        @if ($user->is_hr)
                            <div class="sidebar-section-label">HR</div>
                            <a class="nav-link {{ request()->routeIs('recruitment.*') ? 'active' : '' }}" href="#">
                                <i class="bi bi-person-plus-fill"></i> Tuyển dụng
                            </a>
                            <a class="nav-link {{ request()->routeIs('training.*') ? 'active' : '' }}" href="#">
                                <i class="bi bi-mortarboard-fill"></i> Đào tạo
                            </a>
                            <a class="nav-link {{ request()->routeIs('benefits.*') ? 'active' : '' }}" href="{{ route('benefits.index') }}">
                                <i class="bi bi-gift-fill"></i> Phúc lợi
                            </a>
                        @endif

                    @elseif ($user->is_accountant)
                        @php
                            $acctActive = request()->routeIs('accountant.*');
                        @endphp
                        <div class="nav-item">
                            <div class="accordion" id="accountantAccordion">
                                <div class="accordion-item" style="background:transparent;border:none;">
                                    <button class="accordion-button {{ $acctActive ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#accountantMenu" aria-expanded="{{ $acctActive ? 'true' : 'false' }}">
                                        <i class="bi bi-calculator-fill"></i> Kế toán
                                        <span class="chevron"><i class="bi bi-chevron-right"></i></span>
                                    </button>
                                    <div id="accountantMenu" class="accordion-collapse collapse {{ $acctActive ? 'show' : '' }}" data-bs-parent="#accountantAccordion">
                                        <div class="accordion-body">
                                            <a class="sub-link {{ request()->routeIs('accountant.dashboard') ? 'active' : '' }}" href="{{ route('accountant.dashboard') }}">Dashboard</a>
                                            <a class="sub-link {{ request()->routeIs('accountant.payroll.*') ? 'active' : '' }}" href="{{ route('accountant.payroll.index') }}">Quản lý bảng lương</a>
                                            <a class="sub-link {{ request()->routeIs('payroll.index') || request()->routeIs('payroll.payment.*') ? 'active' : '' }}" href="{{ route('payroll.index') }}">Thanh toán lương</a>
                                            <a class="sub-link {{ request()->routeIs('salary_histories.*') ? 'active' : '' }}" href="{{ route('salary_histories.index') }}">Lịch sử lương</a>
                                            <a class="sub-link {{ request()->routeIs('accountant.payroll.generate') ? 'active' : '' }}" href="{{ route('accountant.payroll.generate') }}">Tính lương</a>
                                            <a class="sub-link {{ request()->routeIs('accountant.payroll.feedback') ? 'active' : '' }}" href="{{ route('accountant.payroll.feedback') }}">Phản hồi lương</a>
                                            <a class="sub-link {{ request()->routeIs('accountant.leave_requests') ? 'active' : '' }}" href="{{ route('accountant.leave_requests') }}">Nghỉ phép</a>
                                            <a class="sub-link {{ request()->routeIs('accountant.allowances') ? 'active' : '' }}" href="{{ route('accountant.allowances') }}">Quản lý phụ cấp</a>
                                            <a class="sub-link {{ request()->routeIs('accountant.deductions') ? 'active' : '' }}" href="{{ route('accountant.deductions') }}">Quản lý khấu trừ</a>
                                            <a class="sub-link {{ request()->routeIs('accountant.bonuses') ? 'active' : '' }}" href="{{ route('accountant.bonuses') }}">Quản lý thưởng</a>
                                            <a class="sub-link {{ request()->routeIs('accountant.reports') ? 'active' : '' }}" href="{{ route('accountant.reports') }}">Báo cáo lương</a>
                                            <a class="sub-link {{ request()->routeIs('accountant.export') ? 'active' : '' }}" href="{{ route('accountant.export') }}">Xuất PDF/Excel</a>
                                            <a class="sub-link {{ request()->routeIs('accountant.activity_logs') ? 'active' : '' }}" href="{{ route('accountant.activity_logs') }}">Nhật ký hoạt động</a>
                                            <a class="sub-link {{ request()->routeIs('accountant.profile') ? 'active' : '' }}" href="{{ route('accountant.profile') }}">Hồ sơ cá nhân</a>
                                            <a class="sub-link {{ request()->routeIs('accountant.password.*') ? 'active' : '' }}" href="{{ route('accountant.password.change') }}">Đổi mật khẩu</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    @else
                        <a class="nav-link {{ request()->routeIs('me.dashboard') ? 'active' : '' }}" href="{{ route('me.dashboard') }}">
                            <i class="bi bi-house-fill"></i> Dashboard
                        </a>
                        <a class="nav-link {{ request()->routeIs('me.profile') || request()->routeIs('me.profile.*') ? 'active' : '' }}" href="{{ route('me.profile') }}">
                            <i class="bi bi-person-fill"></i> Hồ sơ
                        </a>
                        <a class="nav-link {{ request()->routeIs('me.attendance') || request()->routeIs('me.attendance.*') ? 'active' : '' }}" href="{{ route('me.attendance') }}">
                            <i class="bi bi-geo-alt-fill"></i> Chấm công
                        </a>
                        <a class="nav-link {{ request()->routeIs('me.attendance.history') || request()->routeIs('me.attendance.*history') ? 'active' : '' }}" href="{{ route('me.attendance') }}">
                            <i class="bi bi-calendar3"></i> Lịch sử chấm công
                        </a>
                        <a class="nav-link {{ request()->routeIs('me.leave_requests') || request()->routeIs('me.leave_requests.*') ? 'active' : '' }}" href="{{ route('me.leave_requests') }}">
                            <i class="bi bi-journal-text"></i> Đơn xin nghỉ
                        </a>
                        <a class="nav-link {{ request()->routeIs('me.overtime_requests') || request()->routeIs('me.overtime_requests.*') ? 'active' : '' }}" href="{{ route('me.overtime_requests') }}">
                            <i class="bi bi-clock"></i> Đăng ký tăng ca
                        </a>

                        @php
                            $mePayrollActive = request()->routeIs('me.payrolls') || request()->routeIs('me.salary_histories*');
                        @endphp
                        <div class="nav-item">
                            <div class="accordion" id="mePayrollAccordion">
                                <div class="accordion-item" style="background:transparent;border:none;">
                                    <button class="accordion-button {{ $mePayrollActive ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#mePayrollMenu" aria-expanded="{{ $mePayrollActive ? 'true' : 'false' }}">
                                        <i class="bi bi-cash-stack"></i> Lương
                                        <span class="chevron"><i class="bi bi-chevron-right"></i></span>
                                    </button>
                                    <div id="mePayrollMenu" class="accordion-collapse collapse {{ $mePayrollActive ? 'show' : '' }}" data-bs-parent="#mePayrollAccordion">
                                        <div class="accordion-body">
                                            <a class="sub-link {{ request()->routeIs('me.payrolls') ? 'active' : '' }}" href="{{ route('me.payrolls') }}">Bảng lương</a>
                                            <a class="sub-link {{ request()->routeIs('me.salary_histories') ? 'active' : '' }}" href="{{ route('me.salary_histories') }}">Lịch sử lương</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <a class="nav-link {{ request()->routeIs('me.contracts') ? 'active' : '' }}" href="{{ route('me.contracts') }}">
                            <i class="bi bi-file-earmark-text"></i> Hợp đồng
                        </a>
                        <a class="nav-link {{ request()->routeIs('me.evaluations') ? 'active' : '' }}" href="{{ route('me.evaluations') }}">
                            <i class="bi bi-star-fill"></i> Đánh giá
                        </a>
                        <a class="nav-link {{ request()->routeIs('me.benefits') ? 'active' : '' }}" href="{{ route('me.benefits') }}">
                            <i class="bi bi-gift-fill"></i> Phúc lợi
                        </a>
                        <a class="nav-link {{ request()->routeIs('me.notifications') ? 'active' : '' }}" href="{{ route('me.notifications') }}">
                            <i class="bi bi-bell-fill"></i> Thông báo
                        </a>
                        <a class="nav-link {{ request()->routeIs('me.schedule') || request()->routeIs('me.schedule.*') ? 'active' : '' }}" href="{{ route('me.schedule') }}">
                            <i class="bi bi-calendar-week"></i> Lịch làm việc
                        </a>
                        <a class="nav-link {{ request()->routeIs('me.support_requests*') ? 'active' : '' }}" href="{{ route('me.support_requests') }}">
                            <i class="bi bi-ticket-detailed"></i> Yêu cầu hỗ trợ
                        </a>
                        <a class="nav-link {{ request()->routeIs('me.password.*') || request()->routeIs('me.password.change') ? 'active' : '' }}" href="{{ route('me.password.change') }}">
                            <i class="bi bi-lock-fill"></i> Đổi mật khẩu
                        </a>
                        <a class="nav-link {{ request()->routeIs('me.activity_logs') ? 'active' : '' }}" href="{{ route('me.activity_logs') }}">
                            <i class="bi bi-journal-text"></i> Nhật ký hoạt động
                        </a>
                    @endif
                </div>
            </aside>

            <div class="main-content">
                <header class="topbar">
                    <div>
                        <button class="btn d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarToggle" style="border:none;background:transparent;font-size:24px;padding:4px 8px;">
                            <i class="bi bi-list"></i>
                        </button>
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
                        <div class="dropdown">
                            <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('me.profile') }}"><i class="bi bi-person me-2"></i>Hồ sơ</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button class="dropdown-item" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
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
                        <div class="alert alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @yield('content')
                </section>
            </div>
        </div>
    @else
        <main class="auth-page">
            @yield('content')
        </main>
    @endauth
    @stack('scripts')
</body>
</html>
