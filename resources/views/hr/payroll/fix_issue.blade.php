@extends('layouts.app')

@section('title', 'Khắc phục sự cố lương')

@section('content')
<div class="content" style="max-width:920px;">
    <div class="page-head">
        <div>
            <h1>Khắc phục sự cố lương</h1>
            <p class="muted">{{ optional($payroll->employee)->name }} · Tháng {{ $payroll->month }}/{{ $payroll->year }}</p>
        </div>
        <div class="actions">
            <a href="{{ route('payroll.issues.index') }}" class="btn">← Sự cố lương</a>
            <a href="{{ route('payroll.show', $payroll) }}" class="btn">Xem phiếu</a>
        </div>
    </div>

    @if(session('error'))
        <div class="card" style="margin-bottom:16px;background:#fef2f2;border-color:#fecaca;color:#991b1b;">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="card" style="margin-bottom:16px;background:#fef2f2;border-color:#fecaca;color:#991b1b;">
            <ul style="margin:0;padding-left:18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($payroll->issue_report)
        <div class="card" style="margin-bottom:16px;background:#fff7ed;border-color:#fed7aa;">
            <strong style="color:#9a3412;">Nội dung sự cố từ nhân viên</strong>
            <p style="margin:8px 0 0;white-space:pre-wrap;">{{ $payroll->issue_report }}</p>
            @if($payroll->issue_reported_at)
                <p class="muted" style="margin:8px 0 0;font-size:12px;">Báo lúc {{ $payroll->issue_reported_at->format('d/m/Y H:i') }}</p>
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('payroll.issues.fix', $payroll) }}" id="fix-issue-form">
        @csrf

        <div class="grid two-cols">
            <div class="card">
                <h3 style="margin-top:0;">Thông tin chung</h3>
                <div style="margin-bottom:14px;">
                    <span style="color:#64748b;font-size:13px;">Nhân viên</span>
                    <p style="margin:4px 0 0;font-weight:600;">{{ optional($payroll->employee)->name }}</p>
                </div>
                <div style="margin-bottom:14px;">
                    <span style="color:#64748b;font-size:13px;">Email</span>
                    <p style="margin:4px 0 0;font-weight:600;">{{ optional($payroll->employee)->email }}</p>
                </div>
                <div style="margin-bottom:14px;">
                    <span style="color:#64748b;font-size:13px;">Kỳ lương</span>
                    <p style="margin:4px 0 0;font-weight:600;">{{ sprintf('%02d/%d', $payroll->month, $payroll->year) }}</p>
                </div>
                <div style="margin-bottom:14px;">
                    <span style="color:#64748b;font-size:13px;">Trạng thái hiện tại</span>
                    <p style="margin:4px 0 0;"><span class="badge pending">Báo sai sót</span></p>
                </div>

                <div class="field" style="margin-top:18px;">
                    <label for="fix_note">Ghi chú khắc phục (gửi kèm thông báo NV)</label>
                    <textarea id="fix_note" name="fix_note" rows="3" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font:inherit;">{{ old('fix_note') }}</textarea>
                </div>
            </div>

            <div class="card">
                <h3 style="margin-top:0;">Chi tiết số tiền <span class="muted" style="font-weight:500;font-size:13px;">(có thể sửa)</span></h3>

                <div class="field">
                    <label for="base_salary">Lương cơ bản</label>
                    <input type="number" step="0.01" min="0" id="base_salary" name="base_salary" class="money-input"
                           value="{{ old('base_salary', $payroll->base_salary ?? 0) }}" required>
                </div>
                <div class="field">
                    <label for="working_salary">Lương công</label>
                    <input type="number" step="0.01" min="0" id="working_salary" name="working_salary" class="money-input"
                           value="{{ old('working_salary', $payroll->working_salary ?? 0) }}" required>
                </div>
                <div class="field">
                    <label for="overtime_salary">Lương tăng ca</label>
                    <input type="number" step="0.01" min="0" id="overtime_salary" name="overtime_salary" class="money-input"
                           value="{{ old('overtime_salary', $payroll->overtime_salary ?? 0) }}">
                </div>
                <div class="field">
                    <label for="allowance">Phụ cấp (+)</label>
                    <input type="number" step="0.01" min="0" id="allowance" name="allowance" class="money-input"
                           value="{{ old('allowance', $payroll->allowance ?? 0) }}">
                </div>
                <div class="field">
                    <label for="bonus">Thưởng (+)</label>
                    <input type="number" step="0.01" min="0" id="bonus" name="bonus" class="money-input"
                           value="{{ old('bonus', $payroll->bonus ?? 0) }}">
                </div>
                <div class="field">
                    <label for="insurance">BHXH (−)</label>
                    <input type="number" step="0.01" min="0" id="insurance" name="insurance" class="money-input"
                           value="{{ old('insurance', $payroll->insurance ?? 0) }}">
                </div>
                <div class="field">
                    <label for="tax">Thuế (−)</label>
                    <input type="number" step="0.01" min="0" id="tax" name="tax" class="money-input"
                           value="{{ old('tax', $payroll->tax ?? 0) }}">
                </div>
                <div class="field">
                    <label for="deduction">Khấu trừ (−)</label>
                    <input type="number" step="0.01" min="0" id="deduction" name="deduction" class="money-input"
                           value="{{ old('deduction', $payroll->deduction ?? 0) }}">
                </div>
                <div class="field">
                    <label for="late_penalty_fee">Phạt đi muộn (−)</label>
                    <input type="number" step="0.01" min="0" id="late_penalty_fee" name="late_penalty_fee" class="money-input"
                           value="{{ old('late_penalty_fee', $payroll->late_penalty_fee ?? 0) }}">
                    <span style="color:#64748b;font-size:12px;">Tự tính từ chấm công, có thể điều chỉnh</span>
                </div>

                <div style="border-top:1px solid var(--line);padding-top:14px;display:flex;justify-content:space-between;align-items:center;">
                    <span style="color:#64748b;">Thực nhận (tự tính)</span>
                    <strong id="total_preview" style="font-size:24px;color:var(--primary);">
                        {{ number_format($payroll->total_salary ?? 0, 0, '.', ',') }} ₫
                    </strong>
                </div>
            </div>
        </div>

        <div class="actions" style="margin-top:20px;">
            <button type="submit" class="btn primary" onclick="return confirm('Lưu chỉnh sửa? Phiếu sẽ được tính lại và chờ HR kiểm tra, rồi Giám đốc phê duyệt lại.')">
                Lưu & tính lại — chờ HR kiểm tra
            </button>
            <a href="{{ route('payroll.issues.index') }}" class="btn">Hủy</a>
        </div>
    </form>
</div>

<style>
    .field { margin-bottom: 14px; }
    .field label { display:block; font-weight:600; margin-bottom:6px; font-size:13px; color:#475569; }
    .field input { width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px; font:inherit; }
</style>

@push('scripts')
<script>
(function () {
    const ids = ['working_salary', 'overtime_salary', 'allowance', 'bonus', 'insurance', 'tax', 'deduction', 'late_penalty_fee'];
    const preview = document.getElementById('total_preview');
    function num(id) {
        const el = document.getElementById(id);
        return el ? (parseFloat(el.value) || 0) : 0;
    }
    function recalc() {
        const total = num('working_salary') + num('overtime_salary') + num('allowance') + num('bonus')
            - num('insurance') - num('tax') - num('deduction') - num('late_penalty_fee');
        preview.textContent = new Intl.NumberFormat('vi-VN').format(Math.round(total)) + ' ₫';
    }
    ids.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', recalc);
    });
    recalc();
})();
</script>
@endpush
@endsection
