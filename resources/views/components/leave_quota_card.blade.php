@php
    $guides = $guides ?? [];
    $initialType = $initialType ?? array_key_first($guides);
@endphp
<div id="leave-quota-card" class="callout info" data-guides='@json($guides)' hidden>
    <p class="callout-title" id="leave-quota-title">Số ngày được phép</p>
    <div class="row g-3" id="leave-quota-numbers">
        <div class="col-4">
            <p class="muted" style="margin:0 0 4px;font-size:12px;">Được phép</p>
            <p id="leave-quota-allowed" style="margin:0;font-size:22px;font-weight:800;">—</p>
        </div>
        <div class="col-4">
            <p class="muted" style="margin:0 0 4px;font-size:12px;">Đã dùng</p>
            <p id="leave-quota-used" style="margin:0;font-size:22px;font-weight:800;">—</p>
        </div>
        <div class="col-4">
            <p class="muted" style="margin:0 0 4px;font-size:12px;">Còn lại</p>
            <p id="leave-quota-remaining" style="margin:0;font-size:22px;font-weight:800;">—</p>
        </div>
    </div>
    <p class="muted" id="leave-quota-basis" style="margin:12px 0 0;display:none;"></p>
</div>
@once
@push('scripts')
<script>
window.SmartHrLeaveQuota = {
    daysBetween: function (start, end, halfDay) {
        if (!start || !end) return 0;
        var a = new Date(start + 'T00:00:00');
        var b = new Date(end + 'T00:00:00');
        if (Number.isNaN(a.getTime()) || Number.isNaN(b.getTime()) || b < a) return 0;
        var full = Math.round((b - a) / 86400000) + 1;
        if (halfDay && full === 1) return 0.5;
        if (halfDay && full > 1) return full - 0.5;
        return full;
    },
    render: function (guide, requested) {
        var card = document.getElementById('leave-quota-card');
        if (!card || !guide) return;
        card.hidden = false;
        document.getElementById('leave-quota-title').textContent = 'Số ngày được phép · ' + guide.label;
        var allowed = guide.capped ? (guide.allowed + ' ' + guide.unit) : 'Không khống chế';
        var used = guide.capped ? (guide.used + ' ngày') : '—';
        var remaining = guide.capped ? (guide.remaining + ' ngày') : 'Theo giấy / BHXH';
        document.getElementById('leave-quota-allowed').textContent = allowed;
        document.getElementById('leave-quota-used').textContent = used;
        document.getElementById('leave-quota-remaining').textContent = remaining;
        document.getElementById('leave-quota-basis').textContent = guide.basis || '';
        var note = document.getElementById('leave-quota-request');
        if (!requested) {
            note.textContent = 'Chọn ngày bên dưới. Số ngày xin nghỉ không được vượt phần còn lại.';
            note.style.color = '';
            return;
        }
        if (guide.capped && requested > Number(guide.remaining)) {
            note.textContent = 'Đơn này: ' + requested + ' ngày — vượt số ngày còn lại (' + guide.remaining + ').';
            note.style.color = '#dc2626';
        } else {
            note.textContent = 'Đơn này: ' + requested + ' ngày' + (guide.capped ? ' · sau đơn còn ' + Math.max(0, Number(guide.remaining) - requested) + ' ngày.' : '.');
            note.style.color = '';
        }
    },
    bind: function (opts) {
        var card = document.getElementById('leave-quota-card');
        var typeSelect = opts.typeSelect || document.getElementById('leave-type');
        if (!card || !typeSelect) return;
        var guides = {};
        try { guides = JSON.parse(card.getAttribute('data-guides') || '{}'); } catch (e) { guides = {}; }
        var self = this;
        function currentGuide() {
            return guides[typeSelect.value] || null;
        }
        function requestedDays() {
            return self.daysBetween(opts.startInput?.value, opts.endInput?.value, !!opts.halfDay?.checked);
        }
        function sync() {
            self.render(currentGuide(), requestedDays());
        }
        typeSelect.addEventListener('change', sync);
        opts.startInput?.addEventListener('change', sync);
        opts.endInput?.addEventListener('change', sync);
        opts.halfDay?.addEventListener('change', sync);
        sync();
        return {
            setGuides: function (next) {
                guides = next || {};
                card.setAttribute('data-guides', JSON.stringify(guides));
                if (!guides[typeSelect.value]) {
                    var first = Object.keys(guides)[0];
                    if (first) typeSelect.value = first;
                }
                sync();
            }
        };
    }
};
</script>
@endpush
@endonce

