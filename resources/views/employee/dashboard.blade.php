@extends('layouts.app')

@section('title', 'Dashboard nhân viên')

@section('content')
    <div class="page-head">
        <div>
            <h1>Dashboard</h1>
            <p class="muted">Xin chào, {{ $employee->name }}. Đây là trang quản lý cá nhân của bạn.</p>
        </div>
    </div>

    <section class="grid stats">
        <div class="card">
            <h2>Chấm công hôm nay</h2>
            <div class="stat-value">{{ $todayAttendance?->status ? ucfirst($todayAttendance->status) : 'Chưa có' }}</div>
            <p class="muted">Ngày: {{ now()->format('d/m/Y') }}</p>
        </div>
        <div class="card">
            <h2>Chức vụ</h2>
            <div class="stat-value">{{ $employee->position ?? 'Chưa có' }}</div>
            <p class="muted">Phòng ban: {{ optional($employee->department)->name ?? 'Chưa gán' }}</p>
        </div>
        <div class="card">
            <h2>Lương gần nhất</h2>
            <div class="stat-value">{{ $latestPayroll ? number_format($latestPayroll->total_salary, 0, ',', '.') . ' VND' : 'Chưa có' }}</div>
            <p class="muted">{{ $latestPayroll ? $latestPayroll->month : 'Không có lịch sử' }}</p>
        </div>
    </section>

    <div class="grid two-cols" style="margin-top: 22px;">
        <div class="card">
            <h2>Thông tin của bạn</h2>
            <div class="field"><label>Họ tên</label><div>{{ $employee->name }}</div></div>
            <div class="field"><label>Email</label><div>{{ $employee->email }}</div></div>
            <div class="field"><label>Chức vụ</label><div>{{ $employee->position ?? 'Chưa có' }}</div></div>
            <div class="field"><label>Phòng ban</label><div>{{ optional($employee->department)->name ?? 'Chưa gán' }}</div></div>
        </div>
        <div class="card">
            <h2>Hành động nhanh</h2>
            <div class="actions" style="flex-direction: column; align-items: stretch;">
                <a class="btn" href="{{ route('me.profile') }}">Xem hồ sơ</a>
                <a class="btn" href="{{ route('me.attendance') }}">Xem chấm công</a>
                <a class="btn" href="{{ route('me.leave_requests') }}">Xem nghỉ phép</a>
                <a class="btn" href="{{ route('me.payrolls') }}">Xem lương</a>
                <a class="btn" href="{{ route('me.notifications') }}">Xem thông báo</a>
            </div>
        </div>
    </div>
@endsection
