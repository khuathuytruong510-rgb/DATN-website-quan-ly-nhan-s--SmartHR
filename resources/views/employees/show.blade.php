@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>Thông tin nhân viên</h1>
        <p class="muted">Xem chi tiết nhân viên.</p>
    </div>
    <div>
        <a class="btn" href="{{ route('employees.edit', $employee) }}">Sửa thông tin</a>
        <a class="btn link" href="{{ route('employees.index') }}">Quay lại</a>
    </div>
</div>

<div class="card">
    <div class="grid two-cols">
        <div>
            <div class="field"><label>Tên</label><div>{{ $employee->name }}</div></div>
            <div class="field"><label>Email</label><div>{{ $employee->email }}</div></div>
            <div class="field"><label>Chức vụ</label><div>{{ $employee->position }}</div></div>
        </div>
        <div>
            <div class="field"><label>Phòng ban</label><div>{{ optional($employee->department)->name }}</div></div>
            <div class="field"><label>Trạng thái</label><div>{{ ucfirst($employee->status) }}</div></div>
            <div class="field"><label>Ngày tạo</label><div>{{ $employee->created_at->format('d/m/Y H:i') }}</div></div>
        </div>
    </div>
</div>
@endsection
