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

    <div class="alert alert-info" style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #e8f4fd; border-left: 4px solid #2196F3; border-radius: 4px;">
        <strong>Quy định nghỉ phép:</strong> Mỗi nhân viên được phép nghỉ tối đa <strong>2 ngày/tháng</strong>.
        Nếu nhân viên cần nghỉ nhiều hơn, vui lòng đánh dấu là <strong>khẩn cấp</strong> và yêu cầu nhân viên cung cấp lý do thuyết phục.
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
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="half_day" value="1" {{ old('half_day') ? 'checked' : '' }} id="half_day" />
                <strong>Nghỉ 1/2 ngày</strong>
            </label>
            <p class="muted" style="margin: 0.25rem 0 0 1.5rem; font-size: 0.85em;">
                Chỉ áp dụng khi ngày bắt đầu = ngày kết thúc.
            </p>
        </div>

        <div class="form-group full-width">
            <label for="reason">Lý do</label>
            <textarea name="reason" id="reason" rows="4">{{ old('reason', $leaveRequest->reason ?? '') }}</textarea>
        </div>

        <div class="form-group full-width" style="border: 1px solid #ddd; padding: 1rem; border-radius: 4px; background: #fff8e1;">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="is_urgent" value="1" {{ old('is_urgent') ? 'checked' : '' }} id="is_urgent" />
                <strong>Nghỉ phép khẩn cấp (vượt quá 2 ngày/tháng)</strong>
            </label>
            <p class="muted" style="margin: 0.5rem 0 0 1.5rem; font-size: 0.9em;">
                Chỉ chọn khi nhân viên thực sự cần thiết và đã cung cấp lý do thuyết phục.
            </p>
            <div id="urgent_reason_wrapper" style="margin-top: 0.75rem; display: {{ old('is_urgent') ? 'block' : 'none' }};">
                <label for="urgent_reason">Lý do khẩn cấp <span style="color: red;">*</span></label>
                <textarea name="urgent_reason" id="urgent_reason" rows="3" placeholder="Lý do nhân viên cần nghỉ phép vượt quá quy định...">{{ old('urgent_reason') }}</textarea>
            </div>
        </div>

        <div class="form-actions full-width">
            <button type="submit" class="btn primary">Lưu đơn nghỉ</button>
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
