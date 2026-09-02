@extends('layouts.app')

@section('title', $assignment->exists ? 'Cập nhật gán phúc lợi' : 'Gán phúc lợi mới')

@section('content')
<div class="max-w-4xl">
    <div class="page-head">
        <div>
            <h1>{{ $assignment->exists ? 'Cập nhật gán phúc lợi' : 'Gán phúc lợi mới' }}</h1>
            <p class="muted">{{ $assignment->exists ? 'Chỉnh sửa thông tin gán phúc lợi' : 'Thêm phúc lợi cho nhân viên' }}</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert error">
            <h3>Lỗi xác thực</h3>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <form method="POST" action="{{ $assignment->exists ? route('benefits.assignments.update', $assignment) : route('benefits.assignments.store') }}">
            @csrf
            @if($assignment->exists)
                @method('PUT')
            @endif

            <div class="field">
                <label for="employee_id">Nhân viên <span>*</span></label>
                <select id="employee_id" name="employee_id" required>
                    <option value="">-- Chọn nhân viên --</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ old('employee_id', $assignment->employee_id) == $employee->id ? 'selected' : '' }}>
                            {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="benefit_id">Phúc lợi <span>*</span></label>
                <select id="benefit_id" name="benefit_id" required>
                    <option value="">-- Chọn phúc lợi --</option>
                    @foreach($benefits as $benefit)
                        <option value="{{ $benefit->id }}" {{ old('benefit_id', $assignment->benefit_id) == $benefit->id ? 'selected' : '' }}>
                            {{ $benefit->code ? $benefit->code . ' - ' : '' }}{{ $benefit->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="applied_at">Ngày áp dụng <span>*</span></label>
                <input type="date" id="applied_at" name="applied_at" value="{{ old('applied_at', optional($assignment->applied_at)->toDateString()) }}" required>
            </div>

            <div class="field">
                <label for="status">Trạng thái <span>*</span></label>
                <select id="status" name="status" required>
                    <option value="">-- Chọn trạng thái --</option>
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" {{ old('status', $assignment->status) == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="notes">Ghi chú</label>
                <textarea id="notes" name="notes">{{ old('notes', $assignment->notes) }}</textarea>
            </div>

            <div class="actions">
                <button type="submit" class="btn primary">{{ $assignment->exists ? 'Cập nhật' : 'Gán' }}</button>
                <a class="btn" href="{{ route('benefits.assignments.index') }}">Hủy</a>
            </div>
        </form>
    </div>
</div>
@endsection

