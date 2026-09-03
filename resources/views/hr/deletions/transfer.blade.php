@extends('layouts.app')

@section('title', 'Điều chuyển nhân viên')

@section('content')
@php
    $fromFilterId = $fromFilterId ?? null;
    $filteredCandidates = $filteredCandidates ?? collect();
@endphp
<div class="page-head">
    <div>
        <h1>Điều chuyển nhân viên</h1>
    </div>
    <a class="btn link" href="{{ route('employees.index') }}">Quay lại danh sách</a>
</div>

@if(session('success'))
    <div class="alert">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert error">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert error">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <form id="transfer-pick-form" method="GET" action="{{ route('transfers.create') }}">
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <div class="field">
                    <label class="form-label" for="transfer-from">Chọn phòng ban</label>
                    <select id="transfer-from" class="form-select" name="from" required>
                        <option value="">— Chọn phòng ban —</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected((int) $fromFilterId === (int) $department->id)>
                                {{ $department->name }}
                                ({{ $candidates->where('department_id', $department->id)->count() }} NV)
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="field">
                    <label class="form-label" for="transfer-employee">Chọn nhân viên</label>
                    <select id="transfer-employee" class="form-select" name="employee" @disabled(! $fromFilterId) @required($fromFilterId)>
                        <option value="">{{ $fromFilterId ? '— Chọn nhân viên —' : '— Chọn phòng ban trước —' }}</option>
                        @foreach($filteredCandidates as $candidate)
                            <option value="{{ $candidate->id }}" @selected($employee && (int) $employee->id === (int) $candidate->id)>
                                {{ $candidate->name }}
                                @if($candidate->employee_code) — {{ $candidate->employee_code }} @endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </form>
</div>

@if($employee && ! $from)
    <div class="callout warn">
        <p class="callout-title">Chưa thể tạo yêu cầu điều chuyển</p>
        <p>Nhân viên chưa được phân công phòng ban. Vui lòng cập nhật hồ sơ nhân viên trước khi tạo yêu cầu điều chuyển.</p>
        <p style="margin-bottom:0;">
            <a class="btn" href="{{ route('employees.edit', $employee) }}">Cập nhật hồ sơ nhân viên</a>
        </p>
    </div>
@elseif($employee && $from)
    @if($otherDepartments->isEmpty())
        <div class="card">
            <p>Chưa có phòng ban đích khác. Hãy tạo phòng ban mới trước khi điều chuyển.</p>
        </div>
    @else
        <div class="card">
            <form method="POST" action="{{ route('transfers.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="field">
                            <label class="form-label">Phòng ban hiện tại</label>
                            <input class="form-control" type="text" value="{{ $from->name }}" readonly>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="field">
                            <label class="form-label" for="target_department_id">Phòng ban đích</label>
                            <select id="target_department_id" class="form-select @error('target_department_id') is-invalid @enderror" name="target_department_id" required>
                                <option value="">— Chọn phòng ban đích —</option>
                                @foreach($otherDepartments as $target)
                                    <option value="{{ $target->id }}" @selected((string) old('target_department_id') === (string) $target->id)>
                                        {{ $target->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('target_department_id')
                                <p class="error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="field">
                    <label class="form-label" for="transfer-reason">Lý do</label>
                    <textarea id="transfer-reason" class="form-control" name="reason" rows="3" placeholder="Ví dụ: bố trí lại nhân sự, tăng cường dự án...">{{ old('reason') }}</textarea>
                </div>
                <div class="field">
                    <label class="form-label" for="transfer-document">Biên bản/quyết định</label>
                    <input id="transfer-document" class="form-control" type="file" name="document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                </div>
                <div class="actions" style="margin-top:12px;">
                    <button class="btn primary" type="submit" data-confirm="Gửi yêu cầu điều chuyển? Hồ sơ nhân viên chưa đổi phòng cho đến khi Giám đốc duyệt.">Gửi Giám đốc duyệt</button>
                </div>
            </form>
        </div>
    @endif
@endif
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('transfer-pick-form');
    const fromSelect = document.getElementById('transfer-from');
    const employeeSelect = document.getElementById('transfer-employee');
    if (! form || ! fromSelect || ! employeeSelect) return;

    fromSelect.addEventListener('change', () => {
        employeeSelect.value = '';
        employeeSelect.disabled = true;
        form.submit();
    });
    employeeSelect.addEventListener('change', () => {
        if (employeeSelect.value) form.submit();
    });
})();
</script>
@endpush
