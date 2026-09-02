<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SmartHR')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --bg: #f4f7fb;
            --sidebar: #0f172a;
            --sidebar-soft: rgba(255,255,255,.12);
            --panel: #fff;
            --line: #e8eef7;
            --text: #0f172a;
            --muted: #64748b;
            --primary: #2563eb;
            --danger: #dc2626;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", Inter, system-ui, sans-serif; background: var(--bg); color: var(--text); }
        html { scrollbar-width: thin; scrollbar-color: #aab4c2 transparent; }
        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb {
            background: #aab4c2; border-radius: 999px;
            border: 2px solid transparent; background-clip: content-box;
        }
        ::-webkit-scrollbar-thumb:hover { background: #8b97a8; background-clip: content-box; }
        ::-webkit-scrollbar-corner { background: transparent; }
        a { color: inherit; }
        .auth-page {
            min-height: 100vh; display: grid; place-items: center; padding: 24px;
            background: linear-gradient(160deg, #0f172a 0%, #1e1b4b 48%, #312e81 100%);
        }
        .auth-card { width: min(440px, 100%); background: var(--panel); border-radius: 20px; padding: 32px; box-shadow: 0 24px 60px rgba(15, 23, 42, .35); }
        .auth-card h1 { margin: 0 0 8px; font-size: 28px; letter-spacing: -.03em; }
        .auth-brand { font-size: 22px; font-weight: 800; letter-spacing: -.03em; color: #312e81; margin: 0 0 4px; }
        .shell { height: 100vh; overflow: hidden; display: grid; grid-template-columns: 248px 1fr; background: var(--bg); }
        .sidebar {
            background: linear-gradient(180deg, #0f172a 0%, #1e1b4b 55%, #312e81 100%);
            color: #e5e7eb; padding: 22px 16px 28px; max-height: 100vh; overflow-y: auto;
        }
        .brand { font-size: 26px; font-weight: 800; margin-bottom: 6px; letter-spacing: -.03em; }
        .brand-subtitle {
            display: inline-block; margin: 0 0 22px; padding: 4px 10px; border-radius: 999px;
            background: rgba(99,102,241,.22); color: #c7d2fe; font-size: 12px; font-weight: 700;
        }
        .nav { display: grid; gap: 4px; }
        .nav a, .nav-summary {
            display: flex; align-items: center; gap: 10px; text-decoration: none;
            padding: 10px 12px; border-radius: 10px; font-size: 14px; font-weight: 600; color: #cbd5e1;
        }
        .nav a i, .nav-summary i { font-size: 16px; opacity: .9; width: 1.1em; text-align: center; }
        .nav-group { display: block; }
        .nav-group summary { list-style: none; cursor: pointer; margin: 0; }
        .nav-group summary::-webkit-details-marker { display:none; }
        .nav-group a { display: flex; padding: 8px 12px; margin-left: 12px; border-radius: 10px; font-weight: 500; }
        .nav-group[open] .nav-summary, .nav-summary.active,
        .nav a.active, .nav a:hover { background: var(--sidebar-soft); color: #fff; }
        .main { min-width: 0; height: 100vh; display: flex; flex-direction: column; overflow: hidden; }
        .topbar {
            flex: none; position: sticky; top: 0; z-index: 20;
            background: rgba(255,255,255,.92); backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--line); padding: 14px 28px;
            display: flex; justify-content: space-between; align-items: center; gap: 16px;
            box-shadow: 0 1px 0 rgba(15,23,42,.06);
        }
        .topbar-title { font-weight: 800; font-size: 16px; letter-spacing: -.02em; }
        .userbox { display: flex; align-items: center; gap: 12px; }
        .emp-avatar {
            width: 36px; height: 36px; border-radius: 999px; display: grid; place-items: center;
            background: #e0e7ff; color: #3730a3; font-weight: 800; font-size: 13px;
        }
        .userbox .btn { background: #fff; border: 1px solid #e2e8f0; }
        .content { flex: 1 1 auto; min-height: 0; padding: 20px 28px 48px; display: flex; flex-direction: column; overflow-y: auto; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .page-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 16px; }
        .page-head h1 { font-size: 26px; letter-spacing: -.03em; }
        h1 { margin: 0 0 8px; font-size: 32px; }
        .muted { color: var(--muted); margin: 0; }
        .grid { display: grid; gap: 20px; }
        .stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .two-cols { grid-template-columns: 1fr 1fr; }
        .card { background: var(--panel); border: 1px solid var(--line); border-radius: 16px; padding: 24px; box-shadow: 0 8px 24px rgba(15, 23, 42, .04); }
        .contract-page { display: flex; flex-direction: column; gap: 14px; }
        .contract-page .page-head { margin-bottom: 16px; }
        .contract-page .card { padding: 16px 18px; box-shadow: 0 8px 24px rgba(15, 23, 42, .05); }
        .contract-page .card-header { padding: 0 0 10px; margin-bottom: 10px; border-bottom: 1px solid var(--line); }
        .contract-page .card-body { padding: 0.75rem 0 0; }
        .contract-page .container-fluid { padding: 0; }
        .stat-value { font-size: 40px; font-weight: 800; margin: 18px 0 10px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 10px; padding: 10px 14px; font-weight: 700; text-decoration: none; cursor: pointer; background: #f8fafc; color: var(--text); }
        .btn.primary { background: var(--primary); color: #fff; }
        .btn.danger { background: #fee2e2; color: var(--danger); }
        .btn.link { background: transparent; color: var(--primary); padding-left: 0; }
        .actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        table { width: 100%; border-collapse: collapse; }
        .content > table { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 24px rgba(15, 23, 42, .04); }
        .card table { box-shadow: none; }
        th, td { text-align: left; padding: 14px 10px; border-bottom: 1px solid var(--line); vertical-align: top; }
        th { color: var(--muted); font-size: 13px; text-transform: uppercase; background: #f8fafc; }
        .field { display: grid; gap: 7px; margin-bottom: 16px; }
        label { font-weight: 700; }
        input, select, textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 11px 12px; font: inherit; background: #fff; }
        textarea { min-height: 110px; resize: vertical; }
        .error { color: var(--danger); font-size: 13px; }
        .alert { border-radius: 12px; padding: 13px 14px; margin-bottom: 16px; background: #ecfdf5; color: #166534; border: 1px solid #bbf7d0; }
        .badge { display: inline-flex; border-radius: 999px; padding: 5px 9px; font-size: 12px; font-weight: 700; background: #e0f2fe; color: #0369a1; }
        .badge.inactive, .badge.expired { background: #fee2e2; color: var(--danger); }
        .badge.pending { background: #fef3c7; color: #92400e; }
        .empty { background: #f8fafc; padding: 18px; border-radius: 8px; color: var(--muted); }
        .pagination { margin-top: 18px; }
        .max-w-4xl { max-width: 56rem; }
        .mx-auto { margin-left: auto; margin-right: auto; }
        .container { width: 100%; }
        .hidden { display: none !important; }
        .flex { display: flex; }
        .inline-flex { display: inline-flex; }
        .inline { display: inline; }
        .inline-block { display: inline-block; }
        .grid { display: grid; }
        .flex-col { flex-direction: column; }
        .flex-wrap { flex-wrap: wrap; }
        .items-center { align-items: center; }
        .items-start { align-items: flex-start; }
        .justify-between { justify-content: space-between; }
        .gap-2 { gap: .5rem; }
        .gap-3 { gap: .75rem; }
        .gap-4 { gap: 1rem; }
        .space-y-3 > * + * { margin-top: .75rem; }
        .space-y-4 > * + * { margin-top: 1rem; }
        .grid-cols-1 { grid-template-columns: 1fr; }
        .grid-cols-2 { grid-template-columns: 1fr 1fr; }
        .w-full { width: 100%; }
        .h-96 { height: 24rem; }
        .overflow-hidden { overflow: hidden; }
        .rounded-md, .rounded-lg, .rounded-xl, .rounded-2xl { border-radius: 12px; }
        .rounded-full { border-radius: 999px; }
        .border { border: 1px solid var(--line); }
        .border-t { border-top: 1px solid var(--line); }
        .border-b { border-bottom: 1px solid var(--line); }
        .border-gray-100 { border-color: #f3f4f6; }
        .border-gray-200, .border-slate-100, .border-slate-200 { border-color: var(--line); }
        .border-gray-300 { border-color: #cbd5e1; }
        .border-green-200 { border-color: #bbf7d0; }
        .border-red-200 { border-color: #fecaca; }
        .border-amber-200 { border-color: #fde68a; }
        .border-blue-100, .border-blue-200 { border-color: #dbeafe; }
        .shadow-sm, .shadow-md { box-shadow: 0 10px 40px rgba(15, 23, 42, .06); }
        .bg-white { background: #fff; }
        .bg-slate-50, .bg-gray-50 { background: #f8fafc; }
        .bg-green-50 { background: #dcfce7; }
        .bg-red-50 { background: #fee2e2; }
        .bg-amber-50 { background: #fffbeb; }
        .bg-blue-50 { background: #eff6ff; }
        .bg-green-100 { background: #dcfce7; }
        .bg-amber-100 { background: #fef3c7; }
        .bg-slate-100 { background: #f1f5f9; }
        .bg-blue-100 { background: #dbeafe; }
        .bg-green-600, .bg-green-700 { background: #16a34a; color: #fff; }
        .bg-red-600, .bg-red-700 { background: var(--danger); color: #fff; }
        .bg-blue-600, .bg-blue-700 { background: var(--primary); color: #fff; }
        .bg-indigo-600, .bg-indigo-700 { background: #4f46e5; color: #fff; }
        .bg-amber-500 { background: #f59e0b; color: #fff; }
        .bg-emerald-600, .bg-emerald-700 { background: #059669; color: #fff; }
        .bg-violet-600, .bg-violet-700 { background: #7c3aed; color: #fff; }
        .text-white { color: #fff; }
        .text-gray-500, .text-gray-600, .muted { color: var(--muted); }
        .text-gray-700, .text-gray-800, .text-gray-900, .text-slate-700 { color: var(--text); }
        .text-green-700, .text-green-800 { color: #166534; }
        .text-red-600, .text-red-700 { color: var(--danger); }
        .text-amber-800 { color: #92400e; }
        .text-blue-600, .text-blue-800 { color: #1d4ed8; }
        .text-xs { font-size: 12px; }
        .text-sm { font-size: 14px; }
        .text-lg { font-size: 18px; }
        .text-xl { font-size: 20px; }
        .text-2xl { font-size: 24px; }
        .text-3xl { font-size: 30px; }
        .font-medium, .font-semibold, .font-bold, .font-extrabold { font-weight: 700; }
        .p-4 { padding: 1rem; }
        .p-5 { padding: 1.25rem; }
        .p-6 { padding: 1.5rem; }
        .p-8 { padding: 2rem; }
        .px-3 { padding-left: .75rem; padding-right: .75rem; }
        .px-4 { padding-left: 1rem; padding-right: 1rem; }
        .px-5 { padding-left: 1.25rem; padding-right: 1.25rem; }
        .py-2 { padding-top: .5rem; padding-bottom: .5rem; }
        .py-2\.5 { padding-top: .625rem; padding-bottom: .625rem; }
        .py-3 { padding-top: .75rem; padding-bottom: .75rem; }
        .py-8 { padding-top: 2rem; padding-bottom: 2rem; }
        .mb-1 { margin-bottom: .25rem; }
        .mb-2 { margin-bottom: .5rem; }
        .mb-3 { margin-bottom: .75rem; }
        .mb-4 { margin-bottom: 1rem; }
        .mb-6 { margin-bottom: 1.5rem; }
        .mb-8 { margin-bottom: 2rem; }
        .mt-1 { margin-top: .25rem; }
        .mt-2 { margin-top: .5rem; }
        .mt-3 { margin-top: .75rem; }
        .mt-8 { margin-top: 2rem; }
        .mr-2 { margin-right: .5rem; }
        .cursor-pointer { cursor: pointer; }
        .d-flex { display: flex; }
        .d-inline { display: inline; }
        .d-inline-flex { display: inline-flex; }
        .justify-content-between { justify-content: space-between; }
        .justify-content-center { justify-content: center; }
        .align-items-center { align-items: center; }
        .align-items-end { align-items: flex-end; }
        .align-items-stretch { align-items: stretch; }
        .flex-column { flex-direction: column; }
        .container-fluid { width: 100%; }
        .card-body { }
        .border-0 { border: 0; }
        .mb-0 { margin-bottom: 0; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .text-muted { color: var(--muted); }
        .text-success { color: #16a34a; }
        .fw-bold { font-weight: 800; }
        .fs-5 { font-size: 1.15rem; }
        .btn-primary, .btn-success { background: var(--primary); color: #fff; border: 0; border-radius: 8px; padding: 10px 14px; font-weight: 700; cursor: pointer; }
        .btn-success { background: #16a34a; }
        .btn-sm { padding: 6px 10px; font-size: 13px; }
        .btn-outline-primary, .btn-outline-secondary, .btn-outline-danger {
            background: #fff; border: 1px solid var(--line); border-radius: 8px; padding: 8px 12px; font-weight: 700; cursor: pointer; text-decoration: none; color: var(--text);
        }
        .btn-outline-danger { color: var(--danger); border-color: #fecaca; }
        .btn-danger { background: var(--danger); color: #fff; border: 0; border-radius: 8px; padding: 8px 12px; font-weight: 700; }
        .form-select, .form-control { width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 11px 12px; font: inherit; background: #fff; }
        .form-label { font-weight: 700; display: block; margin-bottom: 6px; }
        .row { display: flex; flex-wrap: wrap; gap: 8px; }
        .col-md-3, .col-md-6 { flex: 1; min-width: 140px; }
        .col-md-6 { min-width: 220px; }
        .g-2 { gap: 8px; }
        .text-bg-secondary, .text-bg-info, .text-bg-warning, .text-bg-danger, .text-bg-success { border-radius: 999px; padding: 5px 9px; font-size: 12px; font-weight: 700; }
        .text-bg-secondary { background: #e2e8f0; color: #334155; }
        .text-bg-info { background: #e0f2fe; color: #0369a1; }
        .text-bg-warning { background: #fef3c7; color: #92400e; }
        .text-bg-danger { background: #fee2e2; color: var(--danger); }
        .text-bg-success { background: #dcfce7; color: #166534; }
        .text-wrap { white-space: normal; }
        button.w-full, .w-full.rounded-lg, .w-full.rounded-xl { cursor: pointer; }

        /* ===== Bootstrap màu — tinh chỉnh cho đồng bộ & dễ nhìn ===== */
        .btn-primary, .btn-primary:hover { background: var(--primary); border-color: var(--primary); }
        .btn-success { background: #16a34a; }
        .btn-success:hover { background: #15803d; }
        .btn-danger, .btn-danger:hover { background: var(--danger); }
        .btn-info { background: #e0f2fe; color: #075985; }
        .btn-info:hover { background: #bae6fd; color: #0c4a6e; }
        .btn-warning { background: #fef3c7; color: #78350f; }
        .btn-warning:hover { background: #fde68a; color: #78350f; }
        .btn-secondary { background: #e2e8f0; color: #334155; }
        .btn-secondary:hover { background: #cbd5e1; color: #1e293b; }
        .btn-light { background: #f8fafc; color: #334155; border: 1px solid #e2e8f0; }
        .btn-light:hover { background: #f1f5f9; color: #0f172a; }
        .btn-outline-primary:hover { background: var(--primary); color: #fff; border-color: var(--primary); }
        .btn-outline-secondary:hover { background: #e2e8f0; }
        .btn-outline-info { background: #f0f9ff; border: 1px solid #bae6fd; color: #0369a1; }
        .btn-outline-info:hover { background: #e0f2fe; color: #0c4a6e; }
        .btn-outline-warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
        .btn-outline-warning:hover { background: #fef3c7; color: #78350f; }
        .btn-outline-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
        .btn-outline-success:hover { background: #dcfce7; color: #14532d; }
        .alert-success { background: #dcfce7; color: #14532d; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-warning { background: #fef3c7; color: #78350f; border: 1px solid #fde68a; }
        .alert-info { background: #e0f2fe; color: #0c4a6e; border: 1px solid #bae6fd; }
        .table-success, .table-success > td, .table-success > th { background: #f0fdf4 !important; }
        .table-danger, .table-danger > td, .table-danger > th { background: #fef2f2 !important; }
        .badge.bg-primary { background: #dbeafe !important; color: #1d4ed8 !important; }
        .badge.bg-success { background: #dcfce7 !important; color: #166534 !important; }
        .badge.bg-danger { background: #fee2e2 !important; color: #b91c1c !important; }
        .badge.bg-warning { background: #fef3c7 !important; color: #92400e !important; }
        .badge.bg-info { background: #e0f2fe !important; color: #0369a1 !important; }
        .badge.bg-secondary { background: #e2e8f0 !important; color: #334155 !important; }
        .badge.bg-light { background: #f8fafc !important; color: #334155 !important; border: 1px solid #e2e8f0; }
        .text-primary { color: var(--primary) !important; }
        .text-success { color: #16a34a !important; }
        .text-danger { color: var(--danger) !important; }
        .text-warning { color: #b45309 !important; }
        .text-info { color: #0369a1 !important; }
        .text-secondary { color: #475569 !important; }
        .text-bg-primary { background: var(--primary) !important; color: #fff !important; }
        .text-bg-light { background: #f8fafc !important; color: #334155 !important; border: 1px solid #e2e8f0; }
        .bg-primary { background: var(--primary) !important; }
        .bg-success { background: #16a34a !important; }
        .bg-danger { background: var(--danger) !important; }
        .bg-warning { background: #f59e0b !important; }
        .bg-info { background: #0ea5e9 !important; }
        .bg-secondary { background: #64748b !important; }
        .pagination .page-link { color: var(--primary); border: 1px solid var(--line); border-radius: 8px; margin: 0 3px; }
        .pagination .page-item.active .page-link { background: var(--primary); border-color: var(--primary); color: #fff; }
        .pagination .page-item.disabled .page-link { color: #94a3b8; }
        .pagination .page-link:hover { background: #eff6ff; }
        .form-control:focus, .form-select:focus, .form-check-input:focus { border-color: var(--primary); box-shadow: 0 0 0 .2rem rgba(37, 99, 235, .15); }
        .form-check-input:checked { background-color: var(--primary); border-color: var(--primary); }
        .modal-content { border: 0; border-radius: 16px; box-shadow: 0 24px 60px rgba(15, 23, 42, .25); }
        .btn-close:focus { box-shadow: none; }

        @media (min-width: 768px) {
            .md\:grid-cols-2 { grid-template-columns: 1fr 1fr; }
            .md\:grid-cols-3 { grid-template-columns: 1fr 1fr 1fr; }
            .md\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            .md\:col-span-2 { grid-column: span 2; }
        }
        @media (min-width: 1024px) {
            .lg\:grid-cols-2 { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 900px) {
            .shell { height: auto; overflow: visible; grid-template-columns: 1fr; }
            .main { height: auto; overflow: visible; }
            .content { overflow: visible; flex: none; }
            .stats, .two-cols { grid-template-columns: 1fr; }
            .topbar, .page-head { flex-direction: column; align-items: stretch; }
            .sidebar { max-height: none; }
        }

        /* Thành phần dashboard dùng chung */
        .emp-hero {
            display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;
            background: linear-gradient(135deg, #312e81 0%, #4338ca 48%, #2563eb 100%);
            color: #fff; border-radius: 20px; padding: 26px 28px; margin-bottom: 22px;
            box-shadow: 0 18px 40px rgba(49, 46, 129, .28);
        }
        .emp-hero h1 { color: #fff; margin: 0 0 6px; font-size: 28px; }
        .emp-hero p { margin: 0; color: #c7d2fe; }
        .emp-hero-meta { display: flex; gap: 10px; flex-wrap: wrap; }
        .emp-chip {
            background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.18);
            border-radius: 999px; padding: 6px 12px; font-size: 13px; font-weight: 600;
        }
        .emp-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
        .emp-kpi {
            background: #fff; border: 1px solid #e8eef7; border-radius: 16px; padding: 18px 18px 16px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .04); display: flex; flex-direction: column; min-height: 148px;
            transition: transform .15s ease, box-shadow .15s ease; text-decoration: none; color: inherit;
        }
        a.emp-kpi:hover, .emp-kpi:hover { transform: translateY(-2px); box-shadow: 0 14px 32px rgba(15, 23, 42, .08); }
        .emp-kpi-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 10px; }
        .emp-kpi-label { margin: 0; font-size: 13px; font-weight: 700; color: #64748b; letter-spacing: .01em; }
        .emp-kpi-ico { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; font-size: 18px; }
        .emp-kpi-value { font-size: 26px; font-weight: 800; letter-spacing: -.03em; line-height: 1.15; margin: 0 0 8px; }
        .emp-kpi-sub { margin: 0; font-size: 13px; color: #64748b; flex: 1; }
        .emp-kpi-cta { margin-top: 12px; align-self: flex-start; }
        .emp-kpi.is-warn { border-top: 3px solid #f59e0b; }
        .emp-kpi.is-ok { border-top: 3px solid #10b981; }
        .emp-kpi.is-info { border-top: 3px solid #2563eb; }
        .emp-kpi.is-violet { border-top: 3px solid #7c3aed; }
        .emp-kpi.is-muted { border-top: 3px solid #94a3b8; }
        .emp-kpi.is-danger { border-top: 3px solid #ef4444; }
        .ico-warn { background: #fef3c7; color: #b45309; }
        .ico-ok { background: #d1fae5; color: #047857; }
        .ico-info { background: #dbeafe; color: #1d4ed8; }
        .ico-violet { background: #ede9fe; color: #6d28d9; }
        .ico-muted { background: #f1f5f9; color: #475569; }
        .ico-danger { background: #fee2e2; color: #b91c1c; }
        .emp-progress { height: 8px; background: #e2e8f0; border-radius: 999px; overflow: hidden; margin-top: 10px; }
        .emp-progress > span { display: block; height: 100%; background: #6366f1; border-radius: 999px; }
        .emp-badge { display: inline-flex; align-items: center; border-radius: 999px; padding: 4px 10px; font-size: 12px; font-weight: 700; }
        .emp-badge.ok { background: #d1fae5; color: #047857; }
        .emp-badge.warn { background: #fef3c7; color: #b45309; }
        .emp-badge.info { background: #dbeafe; color: #1d4ed8; }
        .emp-badge.muted { background: #f1f5f9; color: #475569; }
        .emp-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .emp-action {
            display: flex; align-items: center; gap: 12px; padding: 14px; border-radius: 14px;
            text-decoration: none; border: 1px solid #e8eef7; background: #f8fafc; font-weight: 700; color: #0f172a;
        }
        .emp-action:hover { background: #eef2ff; border-color: #c7d2fe; }
        .emp-action i { width: 38px; height: 38px; border-radius: 10px; display: grid; place-items: center; font-size: 18px; }
        .emp-dl { display: grid; gap: 0; }
        .emp-dl > div { display: grid; grid-template-columns: 140px 1fr; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .emp-dl > div:last-child { border-bottom: 0; }
        .emp-dl label { color: #64748b; font-weight: 600; margin: 0; }
        .section-title { font-size: 15px; font-weight: 800; margin: 22px 0 12px; letter-spacing: -.02em; }
        @media (max-width: 1180px) {
            .emp-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 900px) {
            .emp-kpis, .emp-actions { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    @auth
        @php
            $authUser = auth()->user();
            $portalRole = $authUser->is_admin ? 'admin'
                : ($authUser->is_hr ? 'hr'
                : ($authUser->is_director ? 'director'
                : ($authUser->is_accountant ? 'accountant' : 'employee')));
            $portalLabel = match ($portalRole) {
                'admin' => 'Cổng quản trị',
                'hr' => 'Cổng nhân sự',
                'director' => 'Cổng giám đốc',
                'accountant' => 'Cổng kế toán',
                default => 'Cổng nhân viên',
            };
            $nameBits = preg_split('/\s+/u', trim((string) $authUser->name)) ?: ['N'];
            $nameInitials = mb_strtoupper(mb_substr($nameBits[0], 0, 1).mb_substr(end($nameBits) ?: $nameBits[0], 0, 1));
        @endphp
        <div class="shell" data-role="{{ $portalRole }}">
            <aside class="sidebar">
                <div class="brand">SmartHR</div>
                <p class="brand-subtitle">{{ $portalLabel }}</p>
                <nav class="nav">
                    @php $user = $authUser; @endphp
                    @if ($user->is_admin)
                        <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-house"></i>Dashboard</a>
                        <a class="{{ request()->routeIs('accounts.*') ? 'active' : '' }}" href="{{ route('accounts.index') }}"><i class="bi bi-people"></i>Quản lý tài khoản</a>
                        <a class="{{ request()->routeIs('permissions.*') ? 'active' : '' }}" href="{{ route('permissions.index') }}"><i class="bi bi-shield-lock"></i>Phân quyền</a>
                        <a class="{{ request()->routeIs('system_logs.*') ? 'active' : '' }}" href="{{ route('system_logs.index') }}"><i class="bi bi-journal-text"></i>Nhật ký hệ thống</a>
                        <a class="{{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}"><i class="bi bi-gear"></i>Cấu hình hệ thống</a>
                    @elseif ($user->is_hr)
                        <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-house"></i>Dashboard</a>
                        <a class="{{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}"><i class="bi bi-people"></i>Nhân viên</a>
                        <a class="{{ request()->routeIs('departments.*') ? 'active' : '' }}" href="{{ route('departments.index') }}"><i class="bi bi-building"></i>Phòng ban</a>
                        <a class="{{ request()->routeIs('positions.*') ? 'active' : '' }}" href="{{ route('positions.index') }}"><i class="bi bi-briefcase"></i>Chức vụ</a>
                        <a class="{{ request()->routeIs('contracts.*') ? 'active' : '' }}" href="{{ route('contracts.index') }}"><i class="bi bi-file-earmark-text"></i>Hợp đồng</a>
                        <a class="{{ request()->routeIs('attendance.*') ? 'active' : '' }}" href="{{ route('attendance.index') }}"><i class="bi bi-geo-alt"></i>Chấm công</a>
                        <a class="{{ request()->routeIs('evaluations.*') ? 'active' : '' }}" href="{{ route('evaluations.index') }}"><i class="bi bi-star"></i>Đánh giá</a>
                        <a class="{{ request()->routeIs('leave_requests.*') ? 'active' : '' }}" href="{{ route('leave_requests.index') }}"><i class="bi bi-journal-text"></i>Nghỉ phép</a>
                        @php
                            $payrollActive = request()->routeIs('payroll.*')
                                || request()->routeIs('salary_histories.*')
                                || request()->routeIs('salary_payments.*')
                                || request()->routeIs('statistics.*')
                                || request()->routeIs('hr-dashboard.*');
                        @endphp
                        <details class="nav-group" {{ $payrollActive ? 'open' : '' }}>
                            <summary class="nav-summary {{ $payrollActive ? 'active' : '' }}"><i class="bi bi-cash-stack"></i> Lương</summary>
                            <a class="{{ request()->routeIs('payroll.index') ? 'active' : '' }}" href="{{ route('payroll.index') }}">Kiểm tra bảng lương</a>
                            <a class="{{ request()->routeIs('salary_histories.index') ? 'active' : '' }}" href="{{ route('salary_histories.index') }}">Lịch sử lương</a>
                            <a class="{{ request()->routeIs('payroll.bank_requests.*') ? 'active' : '' }}" href="{{ route('payroll.bank_requests.index') }}">Duyệt đổi STK/QR</a>
                            <a class="{{ request()->routeIs('payroll.issues.*') ? 'active' : '' }}" href="{{ route('payroll.issues.index') }}">Sự cố lương</a>
                            <a class="{{ request()->routeIs('salary_payments.*') ? 'active' : '' }}" href="{{ route('salary_payments.index') }}">Lịch sử thanh toán</a>
                            <a class="{{ request()->routeIs('statistics.*') ? 'active' : '' }}" href="{{ route('statistics.index') }}">Thống kê & Báo cáo</a>
                            <a class="{{ request()->routeIs('hr-dashboard.*') ? 'active' : '' }}" href="{{ route('hr-dashboard.index') }}">Báo cáo tổng hợp</a>
                        </details>
                        <a class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}"><i class="bi bi-bell"></i>Thông báo</a>
                        <a class="{{ request()->routeIs('benefits.*') ? 'active' : '' }}" href="{{ route('benefits.index') }}"><i class="bi bi-gift"></i>Phúc lợi</a>
                    @elseif ($user->is_director)
                        <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-house"></i>Dashboard</a>
                        <a class="{{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}"><i class="bi bi-people"></i>Nhân viên</a>
                        <a class="{{ request()->routeIs('contracts.*') ? 'active' : '' }}" href="{{ route('contracts.index') }}"><i class="bi bi-file-earmark-text"></i>Hợp đồng</a>
                        @php
                            $dirPayrollActive = request()->routeIs('payroll.*')
                                || request()->routeIs('salary_histories.*')
                                || request()->routeIs('statistics.*')
                                || request()->routeIs('hr-dashboard.*');
                        @endphp
                        <details class="nav-group" {{ $dirPayrollActive ? 'open' : '' }}>
                            <summary class="nav-summary {{ $dirPayrollActive ? 'active' : '' }}"><i class="bi bi-check2-square"></i> Phê duyệt</summary>
                            <a class="{{ request()->routeIs('payroll.index') || request()->routeIs('payroll.show') ? 'active' : '' }}" href="{{ route('payroll.index') }}">Bảng lương</a>
                            <a class="{{ request()->routeIs('salary_histories.index') ? 'active' : '' }}" href="{{ route('salary_histories.index') }}">Lịch sử lương</a>
                            <a class="{{ request()->routeIs('statistics.*') ? 'active' : '' }}" href="{{ route('statistics.index') }}">Thống kê & Báo cáo</a>
                            <a class="{{ request()->routeIs('hr-dashboard.*') ? 'active' : '' }}" href="{{ route('hr-dashboard.index') }}">Báo cáo tổng hợp</a>
                        </details>
                        <a class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}"><i class="bi bi-bell"></i>Thông báo</a>
                    @elseif ($user->is_accountant)
                        <a class="{{ request()->routeIs('accountant.dashboard') ? 'active' : '' }}" href="{{ route('accountant.dashboard') }}"><i class="bi bi-house"></i>Dashboard</a>
                        <a class="{{ request()->routeIs('accountant.payroll.generate') ? 'active' : '' }}" href="{{ route('accountant.payroll.generate') }}"><i class="bi bi-calculator"></i>Tính lương</a>
                        <a class="{{ request()->routeIs('accountant.payroll.index') || request()->routeIs('accountant.payroll.show') ? 'active' : '' }}" href="{{ route('accountant.payroll.index') }}"><i class="bi bi-table"></i>Bảng lương</a>
                        <a class="{{ request()->routeIs('payroll.index') || request()->routeIs('payroll.payment.*') || request()->routeIs('payroll.show') ? 'active' : '' }}" href="{{ route('payroll.index') }}"><i class="bi bi-wallet2"></i>Thanh toán lương</a>
                        <a class="{{ request()->routeIs('accountant.payroll.feedback') || request()->routeIs('payroll.issues.*') ? 'active' : '' }}" href="{{ route('accountant.payroll.feedback') }}"><i class="bi bi-exclamation-triangle"></i>Sự cố lương</a>
                        <a class="{{ request()->routeIs('accountant.leave_requests') ? 'active' : '' }}" href="{{ route('accountant.leave_requests') }}"><i class="bi bi-calendar-check"></i>Xem nghỉ phép</a>
                        <a class="{{ request()->routeIs('salary_histories.*') ? 'active' : '' }}" href="{{ route('salary_histories.index') }}"><i class="bi bi-clock-history"></i>Lịch sử lương</a>
                        <a class="{{ request()->routeIs('accountant.activity_logs') ? 'active' : '' }}" href="{{ route('accountant.activity_logs') }}"><i class="bi bi-journal-text"></i>Nhật ký</a>
                        <a class="{{ request()->routeIs('accountant.profile') ? 'active' : '' }}" href="{{ route('accountant.profile') }}"><i class="bi bi-person"></i>Hồ sơ</a>
                        <a class="{{ request()->routeIs('accountant.password.*') ? 'active' : '' }}" href="{{ route('accountant.password.change') }}"><i class="bi bi-lock"></i>Đổi mật khẩu</a>
                    @else
                        <a class="{{ request()->routeIs('me.dashboard') ? 'active' : '' }}" href="{{ route('me.dashboard') }}"><i class="bi bi-house"></i>Dashboard</a>
                        <a class="{{ request()->routeIs('me.profile') || request()->routeIs('me.profile.*') ? 'active' : '' }}" href="{{ route('me.profile') }}"><i class="bi bi-person"></i>Hồ sơ</a>
                        <a class="{{ request()->routeIs('me.attendance') || request()->routeIs('me.attendance.*') ? 'active' : '' }}" href="{{ route('me.attendance') }}"><i class="bi bi-geo-alt"></i>Chấm công</a>
                        <a class="{{ request()->routeIs('me.leave_requests') || request()->routeIs('me.leave_requests.*') ? 'active' : '' }}" href="{{ route('me.leave_requests') }}"><i class="bi bi-journal-text"></i>Nghỉ phép</a>
                        <a class="{{ request()->routeIs('me.overtime_requests') || request()->routeIs('me.overtime_requests.*') ? 'active' : '' }}" href="{{ route('me.overtime_requests') }}"><i class="bi bi-clock"></i>Tăng ca</a>
                        @php $mePayrollActive = request()->routeIs('me.payrolls') || request()->routeIs('me.salary_histories*'); @endphp
                        <details class="nav-group" {{ $mePayrollActive ? 'open' : '' }}>
                            <summary class="nav-summary {{ $mePayrollActive ? 'active' : '' }}"><i class="bi bi-cash-stack"></i> Lương</summary>
                            <a class="{{ request()->routeIs('me.payrolls') ? 'active' : '' }}" href="{{ route('me.payrolls') }}">Bảng lương</a>
                            <a class="{{ request()->routeIs('me.salary_histories') ? 'active' : '' }}" href="{{ route('me.salary_histories') }}">Lịch sử lương</a>
                        </details>
                        <a class="{{ request()->routeIs('me.contracts') ? 'active' : '' }}" href="{{ route('me.contracts') }}"><i class="bi bi-file-earmark-text"></i>Hợp đồng</a>
                        <a class="{{ request()->routeIs('me.evaluations') ? 'active' : '' }}" href="{{ route('me.evaluations') }}"><i class="bi bi-star"></i>Đánh giá</a>
                        <a class="{{ request()->routeIs('me.benefits') ? 'active' : '' }}" href="{{ route('me.benefits') }}"><i class="bi bi-gift"></i>Phúc lợi</a>
                        <a class="{{ request()->routeIs('me.notifications') ? 'active' : '' }}" href="{{ route('me.notifications') }}"><i class="bi bi-bell"></i>Thông báo</a>
                        <a class="{{ request()->routeIs('me.schedule') || request()->routeIs('me.schedule.*') ? 'active' : '' }}" href="{{ route('me.schedule') }}"><i class="bi bi-calendar-week"></i>Lịch làm việc</a>
                        <a class="{{ request()->routeIs('me.support_requests*') ? 'active' : '' }}" href="{{ route('me.support_requests') }}"><i class="bi bi-ticket-detailed"></i>Yêu cầu hỗ trợ</a>
                        <a class="{{ request()->routeIs('me.password.*') || request()->routeIs('me.password.change') ? 'active' : '' }}" href="{{ route('me.password.change') }}"><i class="bi bi-lock"></i>Đổi mật khẩu</a>
                        <a class="{{ request()->routeIs('me.activity_logs') ? 'active' : '' }}" href="{{ route('me.activity_logs') }}"><i class="bi bi-journal-text"></i>Nhật ký hoạt động</a>
                    @endif
                </nav>
            </aside>
            <main class="main">
                <header class="topbar">
                    <div>
                        <div class="topbar-title">@yield('title', $portalLabel)</div>
                        <p class="muted">Xin chào, {{ $authUser->name }}</p>
                    </div>
                    <div class="userbox">
                        <span class="emp-avatar" aria-hidden="true">{{ $nameInitials }}</span>
                        <strong>{{ $authUser->name }}</strong>
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
                <section class="content">
                    @hasSection('breadcrumb')
                        <nav aria-label="breadcrumb" style="margin-bottom:12px;">
                            <ol style="list-style:none; padding:0; margin:0; display:flex; gap:8px; align-items:center; font-size:14px; color:var(--muted);">
                                @yield('breadcrumb')
                            </ol>
                        </nav>
                    @endif
                    @if (session('success'))
                        <div class="alert">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert" style="background:#fee2e2;color:#991b1b;">{{ session('error') }}</div>
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

    {{-- Modal xác nhận Bootstrap dùng chung: form có data-confirm hoặc nút/link data-confirm --}}
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalTitle">Xác nhận</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <p id="confirmModalMessage" style="margin:0;white-space:pre-wrap;"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-danger" id="confirmModalOk">Đồng ý</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalEl = document.getElementById('confirmModal');
            if (!modalEl) return;
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            const messageEl = document.getElementById('confirmModalMessage');
            const okBtn = document.getElementById('confirmModalOk');
            let pendingAction = null;
            let pendingCancel = null;

            function openConfirm(message, onConfirm, onCancel) {
                messageEl.textContent = message || 'Bạn có chắc muốn thực hiện?';
                pendingAction = onConfirm;
                pendingCancel = onCancel || null;
                modal.show();
            }

            // Hàm dùng chung cho các script trang muốn mở modal xác nhận.
            window.SmartHrConfirm = openConfirm;

            okBtn.addEventListener('click', function () {
                const action = pendingAction;
                pendingAction = null;
                pendingCancel = null;
                modal.hide();
                if (action) action();
            });

            modalEl.addEventListener('hidden.bs.modal', function () {
                const cancel = pendingCancel;
                pendingAction = null;
                pendingCancel = null;
                if (cancel) cancel();
            });

            // Form: thuộc tính data-confirm trên thẻ <form>
            document.addEventListener('submit', function (e) {
                const form = e.target;
                if (!form.matches || !form.matches('[data-confirm]')) return;
                const message = form.getAttribute('data-confirm');
                e.preventDefault();
                openConfirm(message, function () {
                    form.removeAttribute('data-confirm');
                    form.requestSubmit ? form.requestSubmit() : form.submit();
                });
            });

            // Nút / link: thuộc tính data-confirm
            document.addEventListener('click', function (e) {
                const el = e.target.closest
                    ? e.target.closest('[data-confirm]:not(form)')
                    : null;
                if (!el) return;
                const message = el.getAttribute('data-confirm');
                const href = el.getAttribute('href');
                const formEl = el.closest('form');
                if (href) {
                    e.preventDefault();
                } else if (formEl) {
                    e.preventDefault();
                } else {
                    return;
                }

                openConfirm(message, function () {
                    if (href) {
                        window.location.href = el.href;
                        return;
                    }
                    const submit = function () {
                        formEl.removeAttribute('data-confirm');
                        if (formEl.requestSubmit) formEl.requestSubmit();
                        else formEl.submit();
                    };
                    submit();
                });
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
