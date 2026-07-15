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
            <p class="muted">Xem toàn bộ thông tin cá nhân của bạn.</p>
        </div>
        <div>
            <a class="btn" href="{{ route('me.profile.edit') }}">Chỉnh sửa</a>
        </div>
    </div>

    <div class="card">
        <div class="grid two-cols">
            <div>
                <div class="field"><label>Họ tên</label><div>{{ $employee->name }}</div></div>
                <div class="field"><label>Giới tính</label><div>{{ ucfirst($employee->gender ?? 'Chưa cập nhật') }}</div></div>
                <div class="field"><label>Ngày sinh</label><div>{{ optional($employee->dob)->format('d/m/Y') ?? 'Chưa cập nhật' }}</div></div>
                <div class="field"><label>CCCD</label><div>{{ $employee->cccd ?? 'Chưa cập nhật' }}</div></div>
                <div class="field"><label>Địa chỉ</label><div>{{ $employee->address ?? 'Chưa cập nhật' }}</div></div>
            </div>
            <div>
                <div class="field"><label>Email</label><div>{{ $employee->email }}</div></div>
                <div class="field"><label>Số điện thoại</label><div>{{ $employee->phone ?? 'Chưa cập nhật' }}</div></div>
                <div class="field"><label>Ngày vào làm</label><div>{{ optional($employee->start_date)->format('d/m/Y') ?? 'Chưa cập nhật' }}</div></div>
                <div class="field"><label>Chức vụ</label><div>{{ $employee->position }}</div></div>
                <div class="field"><label>Phòng ban</label><div>{{ optional($employee->department)->name ?? 'Chưa gán' }}</div></div>
                <div class="field"><label>Trình độ</label><div>{{ $employee->education ?? 'Chưa cập nhật' }}</div></div>
                <div class="field"><label>Kinh nghiệm</label><div>{{ $employee->experience ?? 'Chưa cập nhật' }}</div></div>
            </div>
        </div>
    </div>
@endsection
