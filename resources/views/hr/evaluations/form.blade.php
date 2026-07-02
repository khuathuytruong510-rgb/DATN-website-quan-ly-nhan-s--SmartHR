@extends('layouts.app')

@section('title', $evaluation->exists ? 'Cập nhật đánh giá' : 'Tạo đánh giá')

@section('content')
<div class="content">
    <div class="page-head">
        <div>
            <h1>{{ $evaluation->exists ? 'Cập nhật đánh giá' : 'Tạo đánh giá' }}</h1>
            <p class="muted">{{ $evaluation->exists ? 'Chỉnh sửa đánh giá nhân viên theo tháng' : 'Tạo đánh giá hiệu suất cho nhân viên' }}</p>
        </div>
    </div>

    @if ($errors->any())
        <div style="background: #fee2e2; border-left: 4px solid #dc2626; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
            <h3 style="color: #dc2626; margin-top: 0;">Lỗi xác thực</h3>
            <ul style="margin: 10px 0 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <form method="POST" action="{{ $evaluation->exists ? route('evaluations.update', $evaluation) : route('evaluations.store') }}">
            @csrf
            @if($evaluation->exists)
                @method('PUT')
            @endif

            <div class="field">
                <label for="employee_id">Nhân viên <span style="color: #dc2626;">*</span></label>
                <select id="employee_id" name="employee_id" required>
                    <option value="">-- Chọn nhân viên --</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ old('employee_id', $evaluation->employee_id) == $employee->id ? 'selected' : '' }}>
                            {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
                @error('employee_id')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            @if($evaluation->exists && $evaluation->employee)
                <div class="card" style="margin-bottom:24px;">
                    <h2>Thông tin nhân viên</h2>
                    <div class="field"><label>Mã nhân viên</label><div>{{ $evaluation->employee->employee_code ?? '---' }}</div></div>
                    <div class="field"><label>Họ tên</label><div>{{ $evaluation->employee->name }}</div></div>
                    <div class="field"><label>Phòng ban</label><div>{{ optional($evaluation->employee->department)->name ?? '---' }}</div></div>
                    <div class="field"><label>Chức vụ</label><div>{{ $evaluation->employee->position ?? '---' }}</div></div>
                </div>
            @endif

            <div class="field">
                <label for="month">Tháng đánh giá <span style="color: #dc2626;">*</span></label>
                <input type="month" id="month" name="month" value="{{ old('month', $evaluation->month) }}" required>
                @error('month')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <div class="field">
                <label for="rating">Đánh giá chung (1-5) <span style="color: #dc2626;">*</span></label>
                <select id="rating" name="rating" required>
                    <option value="">-- Chọn điểm --</option>
                    @for($i = 1; $i <= 5; $i++)
                        <option value="{{ $i }}" {{ old('rating', $evaluation->rating) == $i ? 'selected' : '' }}>{{ $i }} / 5</option>
                    @endfor
                </select>
                @error('rating')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <div class="card" style="margin-bottom:24px;">
                <h2>Tiêu chí đánh giá</h2>
                <div class="field">
                    <label for="punctuality">Đi đúng giờ (0-10)</label>
                    <input type="number" id="punctuality" name="punctuality" min="0" max="10" value="{{ old('punctuality', $evaluation->punctuality ?? 0) }}" required>
                    @error('punctuality')<span class="error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="task_completion">Hoàn thành công việc (0-30)</label>
                    <input type="number" id="task_completion" name="task_completion" min="0" max="30" value="{{ old('task_completion', $evaluation->task_completion ?? 0) }}" required>
                    @error('task_completion')<span class="error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="quality">Chất lượng công việc (0-20)</label>
                    <input type="number" id="quality" name="quality" min="0" max="20" value="{{ old('quality', $evaluation->quality ?? 0) }}" required>
                    @error('quality')<span class="error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="technical_skill">Kỹ năng chuyên môn (0-10)</label>
                    <input type="number" id="technical_skill" name="technical_skill" min="0" max="10" value="{{ old('technical_skill', $evaluation->technical_skill ?? 0) }}" required>
                    @error('technical_skill')<span class="error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="responsibility">Trách nhiệm (0-10)</label>
                    <input type="number" id="responsibility" name="responsibility" min="0" max="10" value="{{ old('responsibility', $evaluation->responsibility ?? 0) }}" required>
                    @error('responsibility')<span class="error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="teamwork">Làm việc nhóm (0-10)</label>
                    <input type="number" id="teamwork" name="teamwork" min="0" max="10" value="{{ old('teamwork', $evaluation->teamwork ?? 0) }}" required>
                    @error('teamwork')<span class="error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="attitude">Thái độ (0-10)</label>
                    <input type="number" id="attitude" name="attitude" min="0" max="10" value="{{ old('attitude', $evaluation->attitude ?? 0) }}" required>
                    @error('attitude')<span class="error">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="field">
                <label for="summary">Tóm tắt</label>
                <textarea id="summary" name="summary">{{ old('summary', $evaluation->summary) }}</textarea>
                @error('summary')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <div class="field">
                <label for="comments">Nhận xét</label>
                <textarea id="comments" name="comments">{{ old('comments', $evaluation->comments) }}</textarea>
                @error('comments')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <div class="actions" style="margin-top: 32px;">
                <button type="submit" class="btn primary">{{ $evaluation->exists ? 'Cập nhật' : 'Tạo' }}</button>
                <a href="{{ route('evaluations.index') }}" class="btn">Hủy</a>
            </div>
        </form>
    </div>
</div>

<style>
    .content { max-width: 600px; }
    .card { background: var(--panel); border: 1px solid var(--line); border-radius: 12px; padding: 24px; }
    .field { margin-bottom: 20px; }
    label { display: block; font-weight: 700; margin-bottom: 8px; }
    input, select, textarea { width: 100%; padding: 11px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font: inherit; }
    textarea { min-height: 140px; }
    .error { color: #dc2626; font-size: 13px; display: block; margin-top: 5px; }
    .actions { display: flex; gap: 12px; }
    .btn { padding: 10px 16px; border-radius: 8px; border: none; cursor: pointer; font-weight: 700; text-decoration: none; }
    .btn.primary { background: #2563eb; color: white; }
    .btn { background: #f8fafc; color: inherit; }
</style>
@endsection
