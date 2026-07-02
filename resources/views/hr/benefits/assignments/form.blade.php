@extends('layouts.app')

@section('title', $assignment->exists ? 'Cập nhật gán phúc lợi' : 'Gán phúc lợi mới')

@section('content')
<div class="content">
    <div class="page-head">
        <div>
            <h1>{{ $assignment->exists ? 'Cập nhật gán phúc lợi' : 'Gán phúc lợi mới' }}</h1>
            <p class="muted">{{ $assignment->exists ? 'Chỉnh sửa thông tin gán phúc lợi' : 'Thêm phúc lợi cho nhân viên' }}</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert">
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

<style>
    .content { max-width: 720px; }
    .page-head { margin-bottom: 22px; }
    .muted { color: #64748b; }
    .card { background: var(--panel); border: 1px solid var(--line); border-radius: 12px; padding: 24px; }
    .field { margin-bottom: 18px; }
    label { display: block; font-weight: 700; margin-bottom: 8px; }
    input, select, textarea { width: 100%; padding: 11px 14px; border: 1px solid #cbd5e1; border-radius: 8px; }
    textarea { min-height: 120px; }
    .actions { display: flex; gap: 12px; margin-top: 20px; }
    .btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 14px; border-radius: 8px; border: none; text-decoration: none; font-weight: 700; background: #f8fafc; color: inherit; }
    .btn.primary { background: #2563eb; color: #fff; }
    .alert { border-left: 4px solid #dc2626; padding: 16px; background: #fee2e2; margin-bottom: 20px; }
</style>
@endsection
