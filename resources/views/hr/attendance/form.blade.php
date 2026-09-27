@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>{{ $attendance->exists ? 'Chỉnh sửa chấm công' : 'Thêm chấm công' }}</h1>
    </div>
    <a class="btn link" href="{{ route('attendance.index') }}">Quay lại</a>
</div>

<div class="card">
    @if(!$attendance->exists)
        <form method="GET" action="{{ route('attendance.create') }}" class="filter-form" style="margin-bottom:24px;">
            <div class="field-group">
                <label class="form-label" for="department_id">Phòng ban</label>
                <select id="department_id" name="department_id">
                    <option value="">Tất cả phòng ban</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected(($employeeFilters['department_id'] ?? '') == $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field-group">
                <label class="form-label" for="employee_code">Mã nhân viên</label>
                <input type="search" id="employee_code" name="employee_code" value="{{ $employeeFilters['employee_code'] ?? '' }}" placeholder="Nhập mã nhân viên">
            </div>
            <div class="actions-row">
                <button class="btn" type="submit">Lọc nhân viên</button>
                <a class="btn link" href="{{ route('attendance.create') }}">Bỏ lọc</a>
            </div>
        </form>
    @endif

    <form method="POST" action="{{ $attendance->exists ? route('attendance.update', $attendance) : route('attendance.store') }}">
        @csrf
        @if($attendance->exists)
            @method('PUT')
        @endif

        <div class="field">
            <label>Nhân viên</label>
            <select name="employee_id" required>
                <option value="">-- Chọn nhân viên --</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ old('employee_id', $attendance->employee_id) == $employee->id ? 'selected' : '' }}>{{ $employee->name }}{{ $employee->employee_code ? ' — ' . $employee->employee_code : '' }}</option>
                @endforeach
            </select>
            @error('employee_id')<span class="error">{{ $message }}</span>@enderror
        </div>

        <div class="field">
            <label>Ngày</label>
            <input type="date" name="date" value="{{ old('date', $attendance->date) }}" required>
            @error('date')<span class="error">{{ $message }}</span>@enderror
        </div>

        <div class="field">
            <label>Check-in</label>
            <input type="time" name="check_in" value="{{ old('check_in', $attendance->check_in) }}">
            @error('check_in')<span class="error">{{ $message }}</span>@enderror
        </div>

        <div class="field">
            <label>Check-out</label>
            <input type="time" name="check_out" value="{{ old('check_out', $attendance->check_out) }}">
            @error('check_out')<span class="error">{{ $message }}</span>@enderror
        </div>

        <div class="field">
            <label>Trạng thái</label>
            <select name="status" required>
                <option value="present" {{ old('status', $attendance->status) === 'present' ? 'selected' : '' }}>Present</option>
                <option value="absent" {{ old('status', $attendance->status) === 'absent' ? 'selected' : '' }}>Absent</option>
                <option value="late" {{ old('status', $attendance->status) === 'late' ? 'selected' : '' }}>Late</option>
                <option value="leave" {{ old('status', $attendance->status) === 'leave' ? 'selected' : '' }}>Leave</option>
            </select>
            @error('status')<span class="error">{{ $message }}</span>@enderror
        </div>

        <div class="field">
            <label>Ghi chú</label>
            <textarea name="notes">{{ old('notes', $attendance->notes) }}</textarea>
            @error('notes')<span class="error">{{ $message }}</span>@enderror
        </div>

        <div class="actions">
            <button class="btn primary" type="submit">Lưu</button>
            <a class="btn" href="{{ route('attendance.index') }}">Hủy</a>
        </div>
    </form>
</div>
@endsection
