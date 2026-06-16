@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>Thông tin phòng ban</h1>
        <p class="muted">Xem chi tiết và số lượng nhân viên.</p>
    </div>
    <div>
        <a class="btn" href="{{ route('departments.edit', $department) }}">Sửa phòng ban</a>
        <a class="btn link" href="{{ route('departments.index') }}">Quay lại</a>
    </div>
</div>

<div class="card">
    <div class="grid two-cols">
        <div>
            <div class="field"><label>Tên phòng ban</label><div>{{ $department->name }}</div></div>
            <div class="field"><label>Trưởng phòng</label><div>{{ $department->manager ?: '-' }}</div></div>
        </div>
        <div>
            <div class="field"><label>Số lượng nhân viên</label><div>{{ $department->employee_count }}</div></div>
            <div class="field"><label>Mô tả</label><div>{{ $department->description ?: '-' }}</div></div>
        </div>
    </div>
</div>
@endsection
