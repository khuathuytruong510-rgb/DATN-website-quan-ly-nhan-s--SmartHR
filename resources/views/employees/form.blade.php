@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>{{ $employee->exists ? 'Chỉnh sửa nhân viên' : 'Tạo nhân viên mới' }}</h1>
    </div>
    <a class="btn link" href="{{ route('employees.index') }}">Quay lại</a>
</div>

@if(!empty($forDirector))
@endif

<form method="POST" action="{{ $employee->exists ? route('employees.update', $employee) : route('employees.store') }}">
    @csrf
    @if($employee->exists)
        @method('PUT')
    @endif
    @if(!empty($forDirector))
        <input type="hidden" name="for_director" value="1">
    @endif

    <div class="card" style="margin-bottom:16px;">
        <div style="padding:4px 0 14px;border-bottom:1px solid var(--line);margin-bottom:16px;">
            <strong>1. Thông tin cá nhân</strong>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
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
            <div class="field" style="grid-column:1 / -1;">
                <label>Địa chỉ</label>
                <textarea name="address" rows="2">{{ old('address', $employee->address) }}</textarea>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:16px;">
        <div style="padding:4px 0 14px;border-bottom:1px solid var(--line);margin-bottom:16px;">
            <strong>2. Công việc</strong>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="field">
                <label>Mã nhân viên</label>
                <input type="text" id="employeeCodeDisplay" class="form-control" value="{{ $employee->employee_code ?? '' }}" readonly style="background:#f8fafc;color:#64748b;">
                <input type="hidden" name="employee_code" id="employeeCode" value="{{ $employee->employee_code ?? '' }}">
            </div>
            <div class="field">
                <label>Ngày bắt đầu</label>
                <input type="date" name="start_date" value="{{ old('start_date', optional($employee->start_date)->format('Y-m-d')) }}">
            </div>
            <div class="field">
                <label>Phòng ban <span class="text-danger">*</span></label>
                @if($employee->exists && $employee->department_id)
                    @php $currentDept = $departments->firstWhere('id', $employee->department_id); @endphp
                    <input type="hidden" name="department_id" value="{{ $employee->department_id }}">
                    <input class="form-control" type="text" value="{{ $currentDept?->name ?: '—' }}" readonly>
                @else
                    <select name="department_id" id="departmentSelect" required>
                        <option value="">-- Chọn phòng ban --</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id', $employee->department_id) == $dept->id ? 'selected' : '' }}>[{{ $dept->code }}] {{ $dept->name }}</option>
                        @endforeach
                    </select>
                @endif
                @error('department_id')<span class="error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label>Chức vụ</label>
                <select name="position_id" id="positionSelect">
                    <option value="">-- Chọn chức vụ --</option>
                    @foreach($positions as $pos)
                        <option
                            value="{{ $pos->id }}"
                            data-department-id="{{ $pos->department_id }}"
                            data-salary-min="{{ $pos->salary_range_min }}"
                            data-salary-max="{{ $pos->salary_range_max }}"
                            {{ old('position_id', $employee->position_id) == $pos->id ? 'selected' : '' }}
                        >{{ $pos->name }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="position" id="positionName" value="{{ old('position', $employee->position) }}">
                @error('position_id')<span class="error">{{ $message }}</span>@enderror
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:16px;">
        <div style="padding:4px 0 14px;border-bottom:1px solid var(--line);margin-bottom:16px;">
            <strong>3. Học vấn &amp; kinh nghiệm</strong>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="field">
                <label>Trình độ học vấn</label>
                @php
                    $educationOptions = [
                        'THCS' => 'THCS',
                        'THPT' => 'THPT',
                        'Trung cấp' => 'Trung cấp',
                        'Cao đẳng' => 'Cao đẳng',
                        'Đại học' => 'Đại học',
                        'Thạc sĩ' => 'Thạc sĩ',
                        'Tiến sĩ' => 'Tiến sĩ',
                        'Khác' => 'Khác',
                    ];
                    $currentEducation = old('education', $employee->education);
                @endphp
                <select name="education" id="educationSelect">
                    <option value="">-- Chọn trình độ --</option>
                    @foreach($educationOptions as $value => $label)
                        <option value="{{ $value }}" {{ $currentEducation === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                    @if($currentEducation && ! array_key_exists($currentEducation, $educationOptions))
                        <option value="{{ $currentEducation }}" selected>{{ $currentEducation }} (dữ liệu cũ)</option>
                    @endif
                </select>
                @error('education')<span class="error">{{ $message }}</span>@enderror
            </div>
            <div class="field" style="grid-column:1 / -1;">
                <label>Kinh nghiệm</label>
                <textarea name="experience" rows="3">{{ old('experience', $employee->experience) }}</textarea>
            </div>
        </div>
    </div>

    <div class="actions">
        <button class="btn primary" type="submit">Lưu</button>
        <a class="btn" href="{{ route('employees.index') }}">Hủy</a>
    </div>
</form>

<style>
@media (max-width: 768px) {
    .card [style*="grid-template-columns:1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const positionSelect = document.getElementById('positionSelect');
    const positionName = document.getElementById('positionName');
    const positionHint = document.getElementById('positionHint');
    const departmentSelect = document.getElementById('departmentSelect');
    const employeeCodeDisplay = document.getElementById('employeeCodeDisplay');
    const employeeCodeInput = document.getElementById('employeeCode');
    const isEditForm = {{ $employee->exists ? 'true' : 'false' }};
    const lockedDepartmentId = @json(old('department_id', $employee->department_id));

    const allPositionOptions = positionSelect
        ? Array.from(positionSelect.querySelectorAll('option[data-department-id]')).map(opt => opt.cloneNode(true))
        : [];

    function currentDepartmentId() {
        if (departmentSelect) {
            return departmentSelect.value || '';
        }
        return lockedDepartmentId ? String(lockedDepartmentId) : '';
    }

    function filterPositionsByDepartment(preserveSelected = true) {
        if (!positionSelect) return;

        const departmentId = currentDepartmentId();
        const previousValue = preserveSelected ? positionSelect.value : '';

        positionSelect.innerHTML = '';
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = departmentId ? '-- Chọn chức vụ --' : '-- Chọn phòng ban trước --';
        positionSelect.appendChild(placeholder);

        if (!departmentId) {
            positionSelect.value = '';
            if (positionName) positionName.value = '';
            if (positionHint) {
                positionHint.textContent = 'Chọn phòng ban trước để hiện chức vụ thuộc phòng đó.';
            }
            return;
        }

        const matched = allPositionOptions.filter(opt => String(opt.dataset.departmentId) === String(departmentId));
        matched.forEach(opt => positionSelect.appendChild(opt.cloneNode(true)));

        if (preserveSelected && previousValue && matched.some(opt => opt.value === previousValue)) {
            positionSelect.value = previousValue;
        } else {
            positionSelect.value = '';
            if (positionName) positionName.value = '';
        }

        if (positionHint) {
            positionHint.textContent = matched.length
                ? `Hiển thị ${matched.length} chức vụ thuộc phòng ban đã chọn.`
                : 'Phòng ban này chưa có chức vụ. Hãy thêm chức vụ trong mục Chức vụ.';
        }

        const selected = positionSelect.options[positionSelect.selectedIndex];
        if (positionName && selected && selected.value) {
            positionName.value = selected.textContent.trim();
        }
    }

    positionSelect?.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        if (positionName) positionName.value = selected?.value ? selected.textContent.trim() : '';
    });

    departmentSelect?.addEventListener('change', function() {
        filterPositionsByDepartment(false);

        if (isEditForm) {
            return;
        }

        const departmentId = this.value;
        if (!departmentId) {
            if (employeeCodeDisplay) employeeCodeDisplay.value = '';
            if (employeeCodeInput) employeeCodeInput.value = '';
            return;
        }

        fetchNextEmployeeCode(departmentId);
    });

    function fetchNextEmployeeCode(departmentId) {
        if (employeeCodeDisplay) {
            employeeCodeDisplay.value = 'Đang tạo...';
        }
        fetch(`{{ route('employees.next_code') }}?department_id=${encodeURIComponent(departmentId)}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(async response => {
            const data = await response.json().catch(() => null);
            if (!response.ok) {
                throw new Error(data?.error || data?.message || `HTTP ${response.status}`);
            }
            return data;
        })
        .then(data => {
            if (data?.code) {
                if (employeeCodeDisplay) employeeCodeDisplay.value = data.code;
                if (employeeCodeInput) employeeCodeInput.value = data.code;
            } else {
                throw new Error(data?.error || 'Không nhận được mã nhân viên.');
            }
        })
        .catch(error => {
            console.error('Error fetching employee code:', error);
            if (employeeCodeDisplay) employeeCodeDisplay.value = '';
            if (employeeCodeInput) employeeCodeInput.value = '';
            alert('Không tạo được mã nhân viên: ' + (error.message || error));
        });
    }

    filterPositionsByDepartment(true);

    if (departmentSelect && departmentSelect.value && !isEditForm) {
        fetchNextEmployeeCode(departmentSelect.value);
    }
});
</script>
@endsection
