@extends('layouts.app')

@section('title', 'Dashboard - SmartHR')

@section('content')
    <div class="page-head">
        <div>
            <h1>Dashboard</h1>
            <p class="muted">Chào mừng đến SmartHR, hệ thống quản lý nhân sự.</p>
        </div>
    </div>

    <section class="grid stats">
        <div class="card">
            <h2>Phòng ban</h2>
            <div class="stat-value">{{ $departmentCount }}</div>
            <p class="muted">Tổng số phòng ban đang quản lý.</p>
            <a class="btn link" href="{{ route('departments.index') }}">Xem phòng ban</a>
        </div>
        <div class="card">
            <h2>Nhân viên</h2>
            <div class="stat-value">{{ $employeeCount }}</div>
            <p class="muted">Tổng số nhân viên đã được nhập.</p>
            <a class="btn link" href="{{ route('employees.index') }}">Xem nhân viên</a>
        </div>
        <div class="card">
            <h2>Hợp đồng</h2>
            <div class="stat-value">{{ $contractCount }}</div>
            <p class="muted">Tổng số hợp đồng đang quản lý.</p>
            <a class="btn link" href="{{ route('contracts.index') }}">Xem hợp đồng</a>
        </div>
    </section>

    <section class="grid two-cols" style="margin-top: 22px;">
        <div class="card">
            <h2>Nhân viên mới nhất</h2>
            @forelse ($latestEmployees as $employee)
                <div style="padding: 12px 0; border-bottom: 1px solid var(--line);">
                    <strong>{{ $employee->name }}</strong>
                    <p class="muted">{{ $employee->position }} - {{ $employee->department?->name ?? 'Chưa có phòng ban' }}</p>
                </div>
            @empty
                <div class="empty">Không có nhân viên mới.</div>
            @endforelse
        </div>
        <div class="card">
            <h2>Hợp đồng gần nhất</h2>
            @forelse ($latestContracts as $contract)
                <div style="padding: 12px 0; border-bottom: 1px solid var(--line);">
                    <strong>{{ $contract->title }}</strong>
                    <p class="muted">{{ $contract->employee?->name ?? 'Không rõ nhân viên' }} - {{ number_format($contract->salary) }} VND</p>
                </div>
            @empty
                <div class="empty">Không có hợp đồng mới.</div>
            @endforelse
        </div>
    </section>
@endsection
