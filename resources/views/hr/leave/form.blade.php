@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>{{ $leaveRequest->exists ? 'Chỉnh sửa đơn nghỉ phép' : 'Tạo đơn nghỉ phép' }}</h1>
        <p class="muted">Điền thông tin nghỉ phép và gửi duyệt.</p>
    </div>
    <a class="btn link" href="{{ route('leave_requests.index') }}">Quay lại</a>
</div>

<div class="card">
    <form method="POST" action="{{ $leaveRequest->exists ? route('leave_requests.update', $leaveRequest) : route('leave_requests.store') }}">
        @csrf
        @if($leaveRequest->exists)
            @method('PUT')
        @endif

        <div class="field">
            <label>Nhân viên</label>
            <select name="employee_id" required>
                <option value="">-- Chọn nhân viên --</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ old('employee_id', $leaveRequest->employee_id) == $employee->id ? 'selected' : '' }}>{{ $employee->name }}</option>
                @endforeach
            </select>
            @error('employee_id')<span class="error">{{ $message }}</span>@enderror
        </div>

        <div class="two-cols">
            <div class="field">
                <label>Ngày bắt đầu</label>
                <input type="date" name="start_date" value="{{ old('start_date', optional($leaveRequest->start_date)->format('Y-m-d')) }}" required>
                @error('start_date')<span class="error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label>Ngày kết thúc</label>
                <input type="date" name="end_date" value="{{ old('end_date', optional($leaveRequest->end_date)->format('Y-m-d')) }}" required>
                @error('end_date')<span class="error">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="field">
            <label>Loại phép</label>
            <select name="type" required>
                <option value="">-- Chọn loại phép --</option>
                <option value="annual" {{ old('type', $leaveRequest->type) === 'annual' ? 'selected' : '' }}>Nghỉ hàng năm</option>
                <option value="sick" {{ old('type', $leaveRequest->type) === 'sick' ? 'selected' : '' }}>Nghỉ ốm</option>
                <option value="personal" {{ old('type', $leaveRequest->type) === 'personal' ? 'selected' : '' }}>Nghỉ việc riêng</option>
                <option value="unpaid" {{ old('type', $leaveRequest->type) === 'unpaid' ? 'selected' : '' }}>Không lương</option>
            </select>
            @error('type')<span class="error">{{ $message }}</span>@enderror
        </div>

        <div class="field">
            <label>Lý do</label>
            <textarea name="reason">{{ old('reason', $leaveRequest->reason) }}</textarea>
            @error('reason')<span class="error">{{ $message }}</span>@enderror
        </div>

        <div class="actions">
            <button class="btn primary" type="submit">Gửi</button>
            <a class="btn" href="{{ route('leave_requests.index') }}">Hủy</a>
        </div>
    </form>
</div>
@endsection
