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
            <div class="field"><label>Mã phòng ban</label><div><span class="badge bg-secondary">{{ $department->code }}</span></div></div>
            <div class="field"><label>Trưởng phòng</label><div>{{ $department->manager ?: '-' }}</div></div>
        </div>
        <div>
            <div class="field"><label>Số lượng nhân viên</label><div>{{ $department->employee_count }}</div></div>
            <div class="field"><label>Số chức vụ</label><div>{{ $department->positions->count() }}</div></div>
            <div class="field"><label>Mô tả</label><div>{{ $department->description ?: '-' }}</div></div>
        </div>
    </div>
</div>

<div class="card" style="margin-top:20px;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:12px;">
        <div>
            <h2 style="font-size:18px; margin:0 0 4px;">Chức vụ trong phòng ban</h2>
            <p class="muted" style="margin:0;">Các chức vụ tiêu biểu thuộc {{ $department->name }}.</p>
        </div>
        <a class="btn link" href="{{ route('positions.index', ['department' => $department->code]) }}">Xem tại trang Chức vụ</a>
    </div>

    @if ($department->positions->isEmpty())
        <div class="empty">Chưa có chức vụ nào được thiết lập cho phòng ban này.</div>
    @else
        <div class="table-responsive">
            <table class="table" style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="padding:12px; text-align:left; border-bottom:1px solid #e5e7eb;">Tên chức vụ</th>
                        <th style="padding:12px; text-align:left; border-bottom:1px solid #e5e7eb;">Cấp bậc</th>
                        <th style="padding:12px; text-align:left; border-bottom:1px solid #e5e7eb;">Lương cơ bản</th>
                        <th style="padding:12px; text-align:left; border-bottom:1px solid #e5e7eb;">Khoảng lương</th>
                        <th style="padding:12px; text-align:left; border-bottom:1px solid #e5e7eb;">Nhân viên</th>
                        <th style="padding:12px; text-align:left; border-bottom:1px solid #e5e7eb;">Mô tả</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($department->positions as $position)
                        <tr>
                            <td style="padding:12px; border-bottom:1px solid #e5e7eb;"><strong>{{ $position->name }}</strong></td>
                            <td style="padding:12px; border-bottom:1px solid #e5e7eb;"><span class="badge bg-secondary">{{ $position->level }}</span></td>
                            <td style="padding:12px; border-bottom:1px solid #e5e7eb;">{{ number_format($position->base_salary, 0, ',', '.') }} đ</td>
                            <td style="padding:12px; border-bottom:1px solid #e5e7eb;">{{ number_format($position->salary_range_min, 0, ',', '.') }} – {{ number_format($position->salary_range_max, 0, ',', '.') }} đ</td>
                            <td style="padding:12px; border-bottom:1px solid #e5e7eb;">{{ $position->employees_count }}</td>
                            <td style="padding:12px; border-bottom:1px solid #e5e7eb;">{{ $position->description ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
