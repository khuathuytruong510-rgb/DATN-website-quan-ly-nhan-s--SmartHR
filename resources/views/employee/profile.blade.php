@extends('layouts.app')

@section('title', 'Hồ sơ cá nhân')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('me.dashboard') }}">Dashboard</a></li>
<li>Hồ sơ cá nhân</li>
@endsection
    <div class="page-head">
        <div>
            <h1>Hồ sơ cá nhân</h1>
        </div>
        <div>
            <a class="btn primary" href="{{ route('me.profile.edit') }}">Cập nhật thông tin cá nhân</a>
        </div>
    </div>

    <div class="grid two-cols">
        <div class="card">
            <h2>Thông tin nhân viên được cập nhật</h2>
            <div class="field"><label>Họ tên</label><div>{{ $employee->name }}</div></div>
            <div class="field"><label>Giới tính</label><div>{{ match($employee->gender) { 'male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác', default => 'Chưa cập nhật' } }}</div></div>
            <div class="field"><label>Ngày sinh</label><div>{{ optional($employee->dob)->format('d/m/Y') ?? 'Chưa cập nhật' }}</div></div>
            <div class="field"><label>CCCD</label><div>{{ $employee->cccd ?? 'Chưa cập nhật' }}</div></div>
            <div class="field"><label>Địa chỉ</label><div>{{ $employee->address ?? 'Chưa cập nhật' }}</div></div>
            <div class="field"><label>Số điện thoại</label><div>{{ $employee->phone ?? 'Chưa cập nhật' }}</div></div>
        </div>
        <div class="card">
            <h2>Thông tin HR quản lý</h2>
            <div class="field"><label>Mã nhân viên</label><div>{{ $employee->employee_code ?? '—' }}</div></div>
            <div class="field"><label>Email tài khoản</label><div>{{ $employee->email }}</div></div>
            <div class="field"><label>Chức vụ</label><div>{{ $employee->position ?? '—' }}</div></div>
            <div class="field"><label>Phòng ban</label><div>{{ optional($employee->department)->name ?? 'Chưa gán' }}</div></div>
            <div class="field"><label>Ngày vào làm</label><div>{{ optional($employee->start_date)->format('d/m/Y') ?? '—' }}</div></div>
            <div class="field"><label>Loại / trạng thái</label><div>{{ $employee->statusLabel() }}</div></div>
            <div class="field"><label>Trình độ</label><div>{{ $employee->education ?? '—' }}</div></div>
            <div class="field"><label>Lương cơ bản</label><div>{{ ! empty($baseSalary) ? number_format((float) $baseSalary, 0, ',', '.').' ₫' : '—' }}</div></div>
        </div>
    </div>
@endsection
