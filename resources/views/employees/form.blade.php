@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>{{ $employee->exists ? 'Chỉnh sửa nhân viên' : 'Tạo nhân viên mới' }}</h1>
        <p class="muted">Điền đầy đủ thông tin hồ sơ nhân viên.</p>
    </div>
    <a class="btn link" href="{{ route('employees.index') }}">Quay lại</a>
</div>

<div class="card">
    <form method="POST" action="{{ $employee->exists ? route('employees.update', $employee) : route('employees.store') }}">
        @csrf
        @if($employee->exists)
            @method('PUT')
        @endif

        <div class="grid two-cols">
            <div>
                <div class="field">
                    <label>Mã nhân viên</label>
                    <input type="text" id="employeeCodeDisplay" class="form-control" value="{{ $employee->employee_code ?? '' }}" readonly style="background:#f8fafc; color:#64748b;">
                    <input type="hidden" name="employee_code" id="employeeCode" value="{{ $employee->employee_code ?? '' }}">
                    @if (!$employee->exists)
                        <small class="muted" style="display: block; margin-top: 6px;">Mã sẽ được tự động tạo dựa trên phòng ban</small>
                    @endif
                </div>

                <div class="field">
                    <label>Họ và tên <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $employee->name) }}" required>
                    @error('name')<span class="error">{{ $message }}</span>@enderror
                </div>

                <div class="field">
                    <label>Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $employee->email) }}" required>
                    @error('email')<span class="error">{{ $message }}</span>@enderror
                </div>

                <div class="field">
                    <label>Giới tính</label>
                    <select name="gender">
                        <option value="">-- Chọn --</option>
                        <option value="male" {{ old('gender', $employee->gender) == 'male' ? 'selected' : '' }}>Nam</option>
                        <option value="female" {{ old('gender', $employee->gender) == 'female' ? 'selected' : '' }}>Nữ</option>
                        <option value="other" {{ old('gender', $employee->gender) == 'other' ? 'selected' : '' }}>Khác</option>
                    </select>
                </div>

                <div class="field">
                    <label>Ngày sinh</label>
                    <input type="date" name="dob" value="{{ old('dob', optional($employee->dob)->format('Y-m-d')) }}">
                </div>

                <div class="field">
                    <label>CCCD / CMND</label>
                    <input type="text" name="cccd" value="{{ old('cccd', $employee->cccd) }}">
                </div>

                <div class="field">
                    <label>Số điện thoại</label>
                    <input type="text" name="phone" value="{{ old('phone', $employee->phone) }}">
                </div>

                <div class="field">
                    <label>Địa chỉ</label>
                    <textarea name="address">{{ old('address', $employee->address) }}</textarea>
                </div>
            </div>

            <div>
                <div class="field">
                    <label>Phòng ban <span class="text-danger">*</span></label>
                    <select name="department_id" id="departmentSelect" required>
                        <option value="">-- Chọn phòng ban --</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id', $employee->department_id) == $dept->id ? 'selected' : '' }}>[{{ $dept->code }}] {{ $dept->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id')<span class="error">{{ $message }}</span>@enderror
                </div>

                <div class="field">
                    <label>Chức vụ</label>
                    <select name="position_id" id="positionSelect">
                        <option value="">-- Chọn chức vụ --</option>
                        @foreach($positions as $pos)
                            <option value="{{ $pos->id }}" data-salary-min="{{ $pos->salary_range_min }}" data-salary-max="{{ $pos->salary_range_max }}" {{ old('position_id', $employee->position_id) == $pos->id ? 'selected' : '' }}>{{ $pos->name }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="position" id="positionName" value="{{ old('position', $employee->position) }}">
                </div>

                <div class="field">
                    <label>Ngày bắt đầu</label>
                    <input type="date" name="start_date" value="{{ old('start_date', optional($employee->start_date)->format('Y-m-d')) }}">
                </div>

                <div class="field">
                    <label>Trình độ học vấn</label>
                    <input type="text" name="education" value="{{ old('education', $employee->education) }}">
                </div>

                <div class="field">
                    <label>Kinh nghiệm</label>
                    <textarea name="experience">{{ old('experience', $employee->experience) }}</textarea>
                </div>

                <div class="field">
                    <label>Số ngày phép còn lại</label>
                    <input type="number" name="leave_balance" min="0" value="{{ old('leave_balance', $employee->leave_balance ?? 12) }}">
                </div>

                <div class="field">
                    <label>Trạng thái <span class="text-danger">*</span></label>
                    <select name="status" required>
                        <option value="active" {{ old('status', $employee->status) == 'active' ? 'selected' : '' }}>Đang hoạt động</option>
                        <option value="inactive" {{ old('status', $employee->status) == 'inactive' ? 'selected' : '' }}>Ngừng hoạt động</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="actions">
            <button class="btn primary" type="submit">Lưu</button>
            <a class="btn" href="{{ route('employees.index') }}">Hủy</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const positionSelect = document.getElementById('positionSelect');
    const positionName = document.getElementById('positionName');
    const departmentSelect = document.getElementById('departmentSelect');
    const employeeCodeDisplay = document.getElementById('employeeCodeDisplay');
    const employeeCodeInput = document.getElementById('employeeCode');
    const isEditForm = {{ $employee->exists ? 'true' : 'false' }};

    // Handle position change
    positionSelect?.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        positionName.value = selected?.text || '';
    });

    // Handle department change to auto-generate code
    departmentSelect?.addEventListener('change', function() {
        if (isEditForm) {
            // Don't change code for existing employees
            return;
        }

        const departmentId = this.value;
        if (!departmentId) {
            employeeCodeDisplay.value = '';
            employeeCodeInput.value = '';
            return;
        }

        // Fetch next employee code from server
        fetch(`/api/employees/next-code?department_id=${departmentId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.code) {
                employeeCodeDisplay.value = data.code;
                employeeCodeInput.value = data.code;
            } else if (data.error) {
                console.error(data.error);
            }
        })
        .catch(error => console.error('Error fetching employee code:', error));
    });

    // Trigger code generation if department is already selected
    if (departmentSelect && departmentSelect.value && !isEditForm) {
        departmentSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endsection
