@extends('layouts.app')

@section('content')
<div class="page-card">
    <div class="page-header">
        <div>
            <h1>Tạo đơn nghỉ phép</h1>
            <p>Gửi đơn nghỉ phép cho nhân viên được chọn.</p>
        </div>
        <a class="btn" href="{{ route('accountant.leave_requests') }}">Quay lại</a>
    </div>

    <form method="POST" action="{{ route('accountant.leave_requests.store') }}" class="form-grid">
        @csrf

        <div class="form-group">
            <label for="employee_id">Nhân viên</label>
            <select name="employee_id" id="employee_id" required>
                <option value="">-- Chọn nhân viên --</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ old('employee_id', $leaveRequest->employee_id ?? '') == $employee->id ? 'selected' : '' }}>
                        {{ $employee->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="type">Loại nghỉ</label>
            <select name="type" id="type" required>
                <option value="annual" {{ old('type', $leaveRequest->type ?? '') == 'annual' ? 'selected' : '' }}>Nghỉ phép năm</option>
                <option value="sick" {{ old('type', $leaveRequest->type ?? '') == 'sick' ? 'selected' : '' }}>Nghỉ ốm</option>
                <option value="personal" {{ old('type', $leaveRequest->type ?? '') == 'personal' ? 'selected' : '' }}>Nghỉ cá nhân</option>
                <option value="unpaid" {{ old('type', $leaveRequest->type ?? '') == 'unpaid' ? 'selected' : '' }}>Nghỉ không lương</option>
                <option value="maternity" {{ old('type', $leaveRequest->type ?? '') == 'maternity' ? 'selected' : '' }}>Nghỉ thai sản</option>
            </select>
        </div>

        <div class="form-group">
            <label for="start_date">Ngày bắt đầu</label>
            <input type="date" name="start_date" id="start_date" value="{{ old('start_date', $leaveRequest->start_date ?? '') }}" required>
        </div>

        <div class="form-group">
            <label for="end_date">Ngày kết thúc</label>
            <input type="date" name="end_date" id="end_date" value="{{ old('end_date', $leaveRequest->end_date ?? '') }}" required>
        </div>

        <div class="form-group full-width">
            <label for="reason">Lý do</label>
            <textarea name="reason" id="reason" rows="4">{{ old('reason', $leaveRequest->reason ?? '') }}</textarea>
        </div>

        <div class="form-actions full-width">
            <button type="submit" class="btn primary">Lưu đơn nghỉ</button>
        </div>
    </form>
</div>
@endsection
