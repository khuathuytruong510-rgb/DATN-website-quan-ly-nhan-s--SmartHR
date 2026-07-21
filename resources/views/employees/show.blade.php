@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>Thông tin nhân viên</h1>
        <p class="muted">Xem chi tiết hồ sơ nhân viên.</p>
    </div>
    <div>
        <a class="btn" href="{{ route('employees.edit', $employee) }}">Sửa thông tin</a>
        <a class="btn link" href="{{ route('employees.index') }}">Quay lại</a>
    </div>
</div>

<div class="card">
    <div class="grid two-cols">
        <div>
            <div class="field"><label>Mã nhân viên</label><div><code>{{ $employee->employee_code ?? '—' }}</code></div></div>
            <div class="field"><label>Họ và tên</label><div>{{ $employee->name }}</div></div>
            <div class="field"><label>Email</label><div>{{ $employee->email }}</div></div>
            <div class="field"><label>Giới tính</label><div>{{ match($employee->gender ?? null) { 'male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác', default => '—' } }}</div></div>
            <div class="field"><label>Ngày sinh</label><div>{{ optional($employee->dob)->format('d/m/Y') ?? '—' }}</div></div>
            <div class="field"><label>CCCD / CMND</label><div>{{ $employee->cccd ?? '—' }}</div></div>
            <div class="field"><label>Số điện thoại</label><div>{{ $employee->phone ?? '—' }}</div></div>
            <div class="field"><label>Địa chỉ</label><div>{{ $employee->address ?? '—' }}</div></div>
        </div>
        <div>
            <div class="field"><label>Phòng ban</label><div>{{ optional($employee->department)->name ?? '—' }}</div></div>
            <div class="field"><label>Chức vụ</label><div>{{ $employee->position ?? '—' }}</div></div>
            <div class="field"><label>Ngày bắt đầu</label><div>{{ optional($employee->start_date)->format('d/m/Y') ?? '—' }}</div></div>
            <div class="field"><label>Trình độ học vấn</label><div>{{ $employee->education ?? '—' }}</div></div>
            <div class="field"><label>Kinh nghiệm</label><div>{{ $employee->experience ?? '—' }}</div></div>
            <div class="field"><label>Số ngày phép còn lại</label><div>{{ $employee->leave_balance ?? 0 }} ngày</div></div>
            <div class="field"><label>Trạng thái</label><div>{{ $employee->status === 'active' ? 'Đang hoạt động' : 'Ngừng hoạt động' }}</div></div>
            <div class="field"><label>Ngày tạo</label><div>{{ $employee->created_at->format('d/m/Y H:i') }}</div></div>
        </div>
    </div>
</div>
@endsection
