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
    <div class="alert alert-info" style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #e8f4fd; border-left: 4px solid #2196F3; border-radius: 4px;">
        <strong>Quy định nghỉ phép:</strong> Mỗi nhân viên được phép nghỉ tối đa <strong>2 ngày/tháng</strong>.
        Nếu nhân viên cần nghỉ nhiều hơn, vui lòng đánh dấu là <strong>khẩn cấp</strong> và yêu cầu nhân viên cung cấp lý do thuyết phục.
    </div>

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

        <div class="field" style="border: 1px solid #ddd; padding: 1rem; border-radius: 4px; background: #fff8e1;">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="is_urgent" value="1" {{ old('is_urgent', $leaveRequest->is_urgent) ? 'checked' : '' }} id="is_urgent" />
                <strong>Nghỉ phép khẩn cấp (vượt quá 2 ngày/tháng)</strong>
            </label>
            <p class="muted" style="margin: 0.5rem 0 0 1.5rem; font-size: 0.9em;">
                Chỉ chọn khi nhân viên thực sự cần thiết và đã cung cấp lý do thuyết phục.
            </p>
            <div id="urgent_reason_wrapper" style="margin-top: 0.75rem; display: {{ old('is_urgent', $leaveRequest->is_urgent) ? 'block' : 'none' }};">
                <label for="urgent_reason">Lý do khẩn cấp <span style="color: red;">*</span></label>
                <textarea name="urgent_reason" id="urgent_reason" rows="3" placeholder="Lý do nhân viên cần nghỉ phép vượt quá quy định...">{{ old('urgent_reason', $leaveRequest->urgent_reason) }}</textarea>
                @error('urgent_reason')<span class="error">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="actions">
            <button class="btn primary" type="submit">Gửi</button>
            <a class="btn" href="{{ route('leave_requests.index') }}">Hủy</a>
        </div>
    </form>
</div>

<script>
    document.getElementById('is_urgent').addEventListener('change', function() {
        const wrapper = document.getElementById('urgent_reason_wrapper');
        const textarea = document.getElementById('urgent_reason');
        if (this.checked) {
            wrapper.style.display = 'block';
            textarea.required = true;
        } else {
            wrapper.style.display = 'none';
            textarea.required = false;
            textarea.value = '';
        }
    });
</script>
@endsection
