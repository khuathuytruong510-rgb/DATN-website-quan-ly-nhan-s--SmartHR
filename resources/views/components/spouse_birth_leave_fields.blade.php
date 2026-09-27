<div id="spouse-birth-fields" class="callout info" style="display:none;margin:12px 0;">
    <p class="callout-title">Nghỉ thai sản (vợ sinh con)</p>
    <div class="row g-3">
        <div class="col-12 col-md-6">
            <div class="field">
                <label class="form-label" for="children-count">Số con</label>
                <input class="form-control" type="number" id="children-count" name="children_count" min="1" max="255" value="{{ old('children_count', 1) }}">
                @error('children_count')<span class="error">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="field">
                <label class="check-row" for="birth-complication">
                    <input type="checkbox" id="birth-complication" name="birth_complication" value="1" {{ old('birth_complication') ? 'checked' : '' }}>
                    Sinh mổ hoặc có con dưới 32 tuần
                </label>
                @error('birth_complication')<span class="error">{{ $message }}</span>@enderror
            </div>
        </div>
    </div>
    <input type="hidden" id="spouse-birth-days" value="">
    <p class="form-hint" id="spouse-birth-preview" aria-live="polite">Chọn ngày bắt đầu và thông tin sinh để tính ngày kết thúc.</p>
    <div class="field" style="margin-top:12px;">
        <label class="form-label" for="leave-document">Minh chứng (không bắt buộc)</label>
        <input class="form-control" type="file" name="document" id="leave-document" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
        <span class="form-hint">Có thể tải giấy chứng sinh hoặc giấy khai sinh, tối đa 10 MB.</span>
        @error('document')<span class="error">{{ $message }}</span>@enderror
    </div>
</div>

@push('scripts')
<script>
(function () {
    const typeSelect = document.getElementById('leave-type');
    const fields = document.getElementById('spouse-birth-fields');
    const startInput = document.getElementById('leave-start');
    const endInput = document.getElementById('leave-end');
    const countInput = document.getElementById('children-count');
    const complicationInput = document.getElementById('birth-complication');
    const halfDayInput = document.getElementById('half_day');
    const halfDayField = document.getElementById('half-day-field');
    const reasonInput = document.getElementById('leave-reason');
    const documentInput = document.getElementById('leave-document');
    const daysInput = document.getElementById('spouse-birth-days');
    const preview = document.getElementById('spouse-birth-preview');
    if (!typeSelect || !fields || !startInput || !endInput || !countInput || !complicationInput || !preview) return;

    const holidays = new Set(@json($leaveHolidayDates ?? []));
    const offWeekdays = new Set(@json($leaveOffWeekdays ?? [0]));
    let originalReason = reasonInput?.value || '';

    function dateKey(date) {
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    }

    function calculateEndDate(startValue, days) {
        const parts = startValue.split('-').map(Number);
        const date = new Date(parts[0], parts[1] - 1, parts[2], 12);
        let remaining = days;

        while (remaining > 0) {
            if (!offWeekdays.has(date.getDay()) && !holidays.has(dateKey(date))) remaining--;
            if (remaining > 0) date.setDate(date.getDate() + 1);
        }

        return dateKey(date);
    }

    function syncSpouseBirth() {
        const active = typeSelect.value === 'spouse_birth';
        fields.style.display = active ? 'block' : 'none';
        countInput.required = active;
        endInput.readOnly = active;
        endInput.required = !active;
        if (halfDayInput) {
            halfDayInput.disabled = active;
            if (active) halfDayInput.checked = false;
        }
        if (halfDayField) halfDayField.style.display = active ? 'none' : '';
        if (reasonInput) {
            if (active) {
                if (!reasonInput.readOnly) originalReason = reasonInput.value;
                reasonInput.value = 'Vợ sinh con';
                reasonInput.readOnly = true;
            } else if (reasonInput.readOnly) {
                reasonInput.value = originalReason;
                reasonInput.readOnly = false;
            }
        }

        if (!active) {
            if (documentInput) documentInput.value = '';
            if (daysInput) daysInput.value = '';
            preview.textContent = 'Chọn ngày bắt đầu và thông tin sinh để tính ngày kết thúc.';
            return;
        }

        const children = Number.parseInt(countInput.value, 10);
        if (!startInput.value || !Number.isInteger(children) || children < 1 || children > 255) {
            endInput.value = '';
            if (daysInput) daysInput.value = '';
            preview.textContent = 'Nhập số con từ 1 đến 255 và chọn ngày bắt đầu.';
            endInput.dispatchEvent(new Event('change', { bubbles: true }));
            return;
        }

        const complicated = complicationInput.checked;
        const base = children === 1 ? (complicated ? 7 : 5) : (complicated ? 14 : 10);
        const days = base + Math.max(0, children - 2) * 3;
        endInput.value = calculateEndDate(startInput.value, days);
        if (daysInput) daysInput.value = String(days);
        const [year, month, day] = endInput.value.split('-');
        preview.textContent = `${days} ngày làm việc; ngày kết thúc ${day}/${month}/${year}.`;
        endInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    typeSelect.addEventListener('change', syncSpouseBirth);
    startInput.addEventListener('change', syncSpouseBirth);
    countInput.addEventListener('input', syncSpouseBirth);
    complicationInput.addEventListener('change', syncSpouseBirth);
    syncSpouseBirth();
})();
</script>
@endpush