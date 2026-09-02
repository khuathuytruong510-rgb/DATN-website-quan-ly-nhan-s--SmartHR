@extends('layouts.app')

@section('title', 'Đề xuất thăng chức / tăng lương')

@section('content')
@php
    $defaultEffective = now()->addMonthNoOverflow()->startOfMonth()->toDateString();
    $employeesData = $employees->map(function ($e) {
        $latestContract = $e->contracts->sortByDesc('id')->first();
        return [
            'id' => $e->id,
            'name' => $e->name,
            'position' => $e->position,
            'department_id' => $e->department_id,
            'department' => optional($e->department)->name,
            'contract' => $latestContract?->only(['base_salary', 'allowance']) ?? null,
            'salary' => $latestContract?->base_salary
                ?? $e->positionDetail?->base_salary
                ?? $e->positionDetail?->salary_range_min
                ?? 0,
            'allowance' => $latestContract?->allowance ?? $e->positionDetail?->allowance ?? 0,
        ];
    })->values();
    $positionsData = $positions->map(fn ($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'level' => $p->level,
        'department_id' => (int) $p->department_id,
        'base_salary' => (float) $p->base_salary,
        'allowance' => (float) $p->allowance,
        'range_min' => (float) $p->salary_range_min,
        'range_max' => (float) $p->salary_range_max,
    ])->values();
@endphp
<div class="content" style="max-width:900px;">
    <div class="page-head">
        <div>
            <h1>Đề xuất thăng chức / tăng lương</h1>
            <p class="muted">Tạo đề xuất gửi Giám đốc duyệt. Sau khi duyệt, mức lương được cập nhật ngay và thông báo đến nhân viên.</p>
        </div>
        <a class="btn link" href="{{ route('promotion_requests.index') }}">← Danh sách</a>
    </div>

    @if($errors->any())
        <div class="card" style="border-color:#fecaca;background:#fef2f2;margin-bottom:16px;">
            <ul style="margin:0;color:#991b1b;">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('promotion_requests.store') }}" id="promotionForm">
        @csrf

        <div class="card" style="margin-bottom:16px;">
            <h3 style="margin-top:0;">Nhân viên</h3>

            <div class="mb-3">
                <label class="form-label" for="filter_department">Phòng ban</label>
                <select name="filter_department" id="filter_department" class="form-control">
                    <option value="">— Tìm theo phòng ban —</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}" {{ request('dept') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                    @endforeach
                </select>
                <p class="muted" style="margin:6px 0 0;font-size:13px;">Chọn phòng ban để thu hẹp danh sách, sau đó chọn nhân viên — không cần gõ tên.</p>
            </div>

            <div class="mb-3">
                <label class="form-label" for="employee_id">Nhân viên</label>
                <select name="employee_id" id="employee_id" class="form-control" required>
                    <option value="">— Chọn nhân viên —</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}" data-dept="{{ $e->department_id }}" data-id="{{ $e->id }}" {{ (int) old('employee_id', $selectedEmployeeId) === (int) $e->id ? 'selected' : '' }}>
                            {{ $e->name }} · {{ $e->position ?? 'Chưa có chức vụ' }} · {{ optional($e->department)->name ?? '—' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div id="employeeSummary" class="card" style="background:#f8fafc;padding:12px;display:none;"></div>

            <div class="mb-3">
                <label class="form-label">Loại thay đổi</label>
                <div style="display:flex;gap:16px;flex-wrap:wrap;">
                    @foreach($changeTypes as $value => $label)
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                            <input type="radio" name="change_type" value="{{ $value }}" {{ old('change_type', 'promotion') === $value ? 'checked' : '' }}>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom:16px;">
            <h3 style="margin-top:0;">Chức vụ & phòng ban</h3>

            <div class="mb-3">
                <label class="form-label" for="new_position_id">Chức vụ mới</label>
                <select name="new_position_id" id="new_position_id" class="form-control">
                    <option value="">— Chọn chức vụ mới —</option>
                    @foreach($positions as $p)
                        <option value="{{ $p->id }}" {{ (int) old('new_position_id') === (int) $p->id ? 'selected' : '' }}>
                            {{ $p->name }}{{ $p->level ? ' ('.$p->level.')' : '' }} — {{ number_format($p->base_salary ?: $p->salary_range_min, 0, ',', '.') }} ₫{{ $p->allowance ? ' + PC '.number_format($p->allowance, 0, ',', '.').' ₫' : '' }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="new_position" id="new_position" value="{{ old('new_position') }}">
                <p class="muted" style="margin:6px 0 0;font-size:13px;">Không cần chọn nếu chỉ tăng lương giữ chức vụ.</p>
            </div>

            <div class="mb-3">
                <label class="form-label" for="department_id">Phòng ban mới (bỏ trống nếu giữ nguyên)</label>
                <select name="department_id" id="department_id" class="form-control">
                    <option value="">Giữ nguyên phòng ban hiện tại</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}" {{ (int) old('department_id') === (int) $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="card" style="margin-bottom:16px;">
            <h3 style="margin-top:0;">Lương & phụ cấp</h3>

            <div class="grid two-cols">
                <div class="mb-3">
                    <label class="form-label" for="old_base_salary">Lương cơ bản hiện tại (₫)</label>
                    <input type="text" inputmode="numeric" name="old_base_salary" id="old_base_salary" class="form-control salary-input" value="{{ old('old_base_salary', 0) }}" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="old_allowance">Phụ cấp hiện tại (₫)</label>
                    <input type="text" inputmode="numeric" name="old_allowance" id="old_allowance" class="form-control salary-input" value="{{ old('old_allowance', 0) }}" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="new_base_salary">Lương cơ bản mới (₫) <span style="color:#dc2626;">*</span></label>
                    <input type="text" inputmode="numeric" name="new_base_salary" id="new_base_salary" class="form-control salary-input" value="{{ old('new_base_salary') }}" required placeholder="VD: 18.000.000">
                    <p class="muted" style="margin:6px 0 0;font-size:13px;">Khi thăng chức, hệ thống sẽ áp dụng mức lương cơ bản theo lương chuẩn của chức vụ mới.</p>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="new_allowance">Phụ cấp mới (₫)</label>
                    <input type="text" inputmode="numeric" name="new_allowance" id="new_allowance" class="form-control salary-input" value="{{ old('new_allowance', 0) }}" min="0">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="effective_date">Ngày hiệu lực</label>
                <input type="date" name="effective_date" id="effective_date" class="form-control" value="{{ old('effective_date', $defaultEffective) }}" required>
                <p class="muted" style="margin:6px 0 0;font-size:13px;">Mặc định áp dụng từ đầu tháng sau. Bảng lương tháng của kỳ hiệu lực sẽ dùng mức lương này.</p>
            </div>

            <div class="mb-3">
                <label class="form-label" for="new_allowance">Số quyết định / chứng từ</label>
                <input type="text" name="document_number" id="document_number" class="form-control" value="{{ old('document_number') }}" placeholder="VD: QĐ-2026/08-012" maxlength="100">
            </div>

            <div class="mb-3">
                <label class="form-label" for="reason">Lý do đề xuất <span style="color:#dc2626;">*</span></label>
                <textarea name="reason" id="reason" class="form-control" rows="4" required placeholder="VD: Đạt KPI xuất sắc 03 tháng liên tiếp, hoàn thành khóa đào tạo quản lý...">{{ old('reason') }}</textarea>
            </div>
        </div>

        <div style="display:flex;gap:12px;align-items:center;">
            <button type="submit" class="btn primary">Gửi đề xuất lên Giám đốc</button>
            <a class="btn link" href="{{ route('promotion_requests.index') }}">Hủy</a>
        </div>
    </form>
</div>

<script>
(function () {
    const employees = @json($employeesData);
    const positions = @json($positionsData);
    const byId = (arr) => Object.fromEntries(arr.map((x) => [String(x.id), x]));

    const emap = byId(employees);
    const pmap = byId(positions);

    const $filterDept = document.getElementById('filter_department');
    const $emp = document.getElementById('employee_id');
    const $summary = document.getElementById('employeeSummary');
    const $oldSalary = document.getElementById('old_base_salary');
    const $oldAllowance = document.getElementById('old_allowance');
    const $newSalary = document.getElementById('new_base_salary');
    const $newAllowance = document.getElementById('new_allowance');
    const $newPositionId = document.getElementById('new_position_id');
    const $newPosition = document.getElementById('new_position');
    const $deptNew = document.getElementById('department_id');

    const employeeOptions = Array.prototype.map.call($emp.options, function (o) {
        return { value: o.value, dept: o.dataset.dept ? String(o.dataset.dept) : '', text: o.textContent.trim() };
    });

    const positionOptions = Array.prototype.map.call($newPositionId.options, function (o) {
        const p = pmap[String(o.value)];
        return { value: o.value, dept: p ? String(p.department_id) : '', text: o.textContent.trim() };
    });

    function fmt(n) {
        return Number(n || 0).toLocaleString('vi-VN');
    }

    function toPlain(v) {
        return String(v == null ? '' : v).replace(/[^\d]/g, '');
    }

    function formatSalaryInput(el) {
        const plain = toPlain(el.value);
        el.value = plain === '' ? '' : Number(plain).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    document.querySelectorAll('.salary-input').forEach(function (el) {
        el.addEventListener('input', function () {
            formatSalaryInput(el);
        });
    });
    document.getElementById('promotionForm').addEventListener('submit', function () {
        document.querySelectorAll('.salary-input').forEach(function (el) {
            el.value = toPlain(el.value);
        });
    });

    function refreshEmployee() {
        const emp = emap[String($emp.value)];
        if (!emp) {
            $summary.style.display = 'none';
            return;
        }
        $summary.style.display = 'block';
        $summary.innerHTML =
            '<div><strong>' + emp.name + '</strong></div>' +
            '<div class="muted" style="font-size:13px;">Chức vụ: ' + (emp.position || '—') +
            ' · Phòng ban: ' + (emp.department || '—') + '</div>' +
            '<div class="muted" style="font-size:13px;">Lương cơ bản: ' + fmt(emp.salary) +
            ' ₫ · Phụ cấp: ' + fmt(emp.allowance) + ' ₫</div>';

        const salary = Number(emp.salary || 0);
        const allowance = Number(emp.allowance || 0);
        $oldSalary.value = salary;
        $oldAllowance.value = allowance;
        formatSalaryInput($oldSalary);
        formatSalaryInput($oldAllowance);

        if (!$newSalary.value || Number(toPlain($newSalary.value)) <= 0) {
            $newSalary.value = salary > 0 ? salary : '';
        }
        if (!$newAllowance.value || Number(toPlain($newAllowance.value)) <= 0) {
            $newAllowance.value = allowance || 0;
        }
        formatSalaryInput($newSalary);
        formatSalaryInput($newAllowance);

        refreshPositionOptions();
    }

    function refreshPosition() {
        const pos = pmap[String($newPositionId.value)];
        $newPosition.value = pos ? pos.name : '';
        if (!pos) {
            return;
        }
        $newSalary.value = pos.base_salary > 0 ? pos.base_salary : (pos.range_min > 0 ? pos.range_min : 0);
        $newAllowance.value = pos.allowance || 0;
        formatSalaryInput($newSalary);
        formatSalaryInput($newAllowance);
    }

    function refreshEmployeeOptions() {
        const dept = String($filterDept.value || '');
        const selected = String($emp.value || '');

        let html = '<option value="">— Chọn nhân viên —</option>';
        let kept = null;
        employeeOptions.forEach(function (o) {
            const match = dept === '' || String(o.dept) === dept;
            if (!match) return;
            const sel = String(o.value) === selected ? ' selected' : '';
            if (String(o.value) === selected) kept = o.value;
            html += '<option value="' + o.value + '" data-dept="' + o.dept + '"' + sel + '>' + o.text + '</option>';
        });
        $emp.innerHTML = html;

        if (selected && kept === null) {
            refreshEmployee();
        }
    }

    function currentTargetDept() {
        const dep = String($deptNew.value || '');
        if (dep) return dep;
        const emp = emap[String($emp.value || '')];
        return emp && emp.department_id ? String(emp.department_id) : '';
    }

    function refreshPositionOptions() {
        const dept = currentTargetDept();
        const selected = String($newPositionId.value || '');

        let html = '<option value="">— Chọn chức vụ mới —</option>';
        let kept = null;
        positionOptions.forEach(function (o) {
            if (dept !== '' && String(o.dept) !== dept) return;
            const sel = String(o.value) === selected ? ' selected' : '';
            if (String(o.value) === selected) kept = o.value;
            html += '<option value="' + o.value + '"' + sel + '>' + o.text + '</option>';
        });
        $newPositionId.innerHTML = html;

        if (selected && kept === null) {
            $newPosition.value = '';
        }
    }

    $filterDept.addEventListener('change', function () {
        $emp.value = '';
        refreshEmployeeOptions();
        refreshEmployee();
    });

    $emp.addEventListener('change', refreshEmployee);
    $newPositionId.addEventListener('change', refreshPosition);
    $deptNew.addEventListener('change', refreshPositionOptions);

    refreshEmployeeOptions();
    refreshPositionOptions();
    if ($emp.value) refreshEmployee();
    if ($newPositionId.value) refreshPosition();
})();
</script>
@endsection