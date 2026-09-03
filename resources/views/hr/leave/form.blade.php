@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>{{ $leaveRequest->exists ? 'Chỉnh sửa đơn nghỉ phép' : 'Tạo đơn nghỉ phép' }}</h1>
    </div>
    <a class="btn link" href="{{ route('leave_requests.index') }}">Quay lại</a>
</div>

<div class="card">
    <form method="POST" action="{{ $leaveRequest->exists ? route('leave_requests.update', $leaveRequest) : route('leave_requests.store') }}" data-confirm="Xác nhận gửi? Hệ thống sẽ kiểm tra điều kiện nghỉ phép trên hợp đồng và luật lao động trước khi chuyển duyệt.">
        @csrf
        @if($leaveRequest->exists)
            @method('PUT')
        @endif

        <div class="field">
            <label class="form-label" for="leave-employee">Nhân viên</label>
            <select name="employee_id" id="leave-employee" class="form-select" required>
                <option value="">-- Chọn nhân viên --</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" data-female="{{ $employee->isFemale() ? '1' : '0' }}" {{ old('employee_id', $leaveRequest->employee_id) == $employee->id ? 'selected' : '' }}>{{ $employee->name }}</option>
                @endforeach
            </select>
            @error('employee_id')<span class="error">{{ $message }}</span>@enderror
        </div>

        <div class="field">
            <label class="form-label" for="leave-type">Loại phép</label>
            @include('components.leave_type_select', ['selected' => old('type', $leaveRequest->type ?: ($defaultType ?? null))])
            @error('type')<span class="error">{{ $message }}</span>@enderror
        </div>

        @include('components.leave_quota_card', ['guides' => []])

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <div class="field">
                    <label class="form-label">Ngày bắt đầu</label>
                    <input class="form-control" id="leave-start" type="date" name="start_date" value="{{ old('start_date', optional($leaveRequest->start_date)->format('Y-m-d')) }}" required>
                    @error('start_date')<span class="error">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="field">
                    <label class="form-label">Ngày kết thúc</label>
                    <input class="form-control" id="leave-end" type="date" name="end_date" value="{{ old('end_date', optional($leaveRequest->end_date)->format('Y-m-d')) }}" required>
                    @error('end_date')<span class="error">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>

        <div class="field">
            <label class="check-row">
                <input type="checkbox" name="half_day" value="1" {{ old('half_day', $leaveRequest->half_day) ? 'checked' : '' }} id="half_day" />
                Nghỉ 1/2 ngày
            </label>
        </div>

        <div class="field">
            <label class="form-label">Lý do</label>
            <textarea class="form-control" name="reason">{{ old('reason', $leaveRequest->reason) }}</textarea>
            @error('reason')<span class="error">{{ $message }}</span>@enderror
        </div>

        <div class="callout warn">
            <label class="check-row">
                <input type="checkbox" name="is_urgent" value="1" {{ old('is_urgent', $leaveRequest->is_urgent) ? 'checked' : '' }} id="is_urgent" />
                Đánh dấu khẩn cấp (ưu tiên duyệt)
            </label>
            <div id="urgent_reason_wrapper" style="margin-top: 12px; display: {{ old('is_urgent', $leaveRequest->is_urgent) ? 'block' : 'none' }};">
                <label class="form-label" for="urgent_reason">Lý do khẩn cấp</label>
                <textarea class="form-control" name="urgent_reason" id="urgent_reason" rows="3" placeholder="Lý do nhân viên cần nghỉ phép...">{{ old('urgent_reason', $leaveRequest->urgent_reason) }}</textarea>
                @error('urgent_reason')<span class="error">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="actions">
            <button class="btn primary" type="submit">Xác nhận gửi</button>
            <a class="btn" href="{{ route('leave_requests.index') }}">Hủy</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const employeeSelect = document.getElementById('leave-employee');
        const typeSelect = document.getElementById('leave-type');
        const maternity = typeSelect?.querySelector('option[value="maternity"]');
        const guidesByEmployee = @json($employeeGuides ?? []);

        function syncMaternity() {
            if (!typeSelect || !maternity) return;
            const option = employeeSelect?.selectedOptions?.[0];
            const female = option?.dataset?.female === '1';
            maternity.hidden = !female;
            maternity.disabled = !female;
            if (!female && typeSelect.value === 'maternity') {
                typeSelect.value = 'annual';
            }
        }

        const quota = window.SmartHrLeaveQuota?.bind({
            typeSelect: typeSelect,
            startInput: document.getElementById('leave-start'),
            endInput: document.getElementById('leave-end'),
            halfDay: document.getElementById('half_day'),
        });

        function syncGuides() {
            syncMaternity();
            const id = employeeSelect?.value;
            quota?.setGuides(id ? (guidesByEmployee[id] || {}) : {});
        }

        employeeSelect?.addEventListener('change', syncGuides);
        syncGuides();
    })();

    document.getElementById('is_urgent')?.addEventListener('change', function() {
        const wrapper = document.getElementById('urgent_reason_wrapper');
        const textarea = document.getElementById('urgent_reason');
        if (!wrapper || !textarea) return;
        wrapper.style.display = this.checked ? 'block' : 'none';
        textarea.required = this.checked;
        if (!this.checked) textarea.value = '';
    });
</script>
@endpush
