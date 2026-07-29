@extends('layouts.app')
@section('title', $evaluation->exists ? 'Cập nhật đánh giá' : 'Tạo đánh giá')

@section('content')
<div style="max-width:1100px;">

<div class="page-head">
    <div>
        <h1>{{ $evaluation->exists ? 'Cập nhật đánh giá' : 'Tạo đánh giá mới' }}</h1>
        <p class="muted">Hệ thống tự động tải dữ liệu thực tế và đề xuất điểm khi bạn chọn nhân viên + tháng</p>
    </div>
    <a href="{{ route('evaluations.index') }}" class="btn">← Quay lại</a>
</div>

@if($errors->any())
<div style="background:#fee2e2;border-left:4px solid #dc2626;padding:14px 16px;border-radius:8px;margin-bottom:18px;">
    <strong style="color:#dc2626">Lỗi:</strong>
    <ul style="margin:6px 0 0;padding-left:18px;">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<div style="display:grid;grid-template-columns:1fr 400px;gap:20px;align-items:start;">

{{-- ============================================================
     LEFT: FORM
============================================================ --}}
<div>
<div class="card" style="padding:24px;">
<form id="evalForm" method="POST"
      action="{{ $evaluation->exists ? route('evaluations.update',$evaluation) : route('evaluations.store') }}">
    @csrf
    @if($evaluation->exists) @method('PUT') @endif

    <div class="field">
        <label>Nhân viên <span style="color:#dc2626">*</span></label>
        <select id="sel_employee" name="employee_id" required onchange="onParamChange()">
            <option value="">-- Chọn nhân viên --</option>
            @foreach($employees as $emp)
            <option value="{{ $emp->id }}"
                {{ old('employee_id', $evaluation->employee_id) == $emp->id ? 'selected' : '' }}>
                {{ $emp->name }}{{ $emp->department ? ' ('.$emp->department->name.')' : '' }}
            </option>
            @endforeach
        </select>
    </div>

    <div class="field">
        <label>Tháng đánh giá <span style="color:#dc2626">*</span></label>
        <input type="month" id="sel_month" name="month"
               value="{{ old('month', $evaluation->month ?? $month) }}"
               required onchange="onParamChange()">
    </div>

    <div id="loadBtnWrap" style="margin-bottom:16px;{{ $monthlyStats ? 'display:none;' : '' }}">
        <button type="button" class="btn primary" onclick="fetchSuggest()">
            ⟳ Tải dữ liệu thực tế &amp; Đề xuất điểm
        </button>
        <span id="loadSpinner" style="display:none;margin-left:10px;color:#64748b;font-size:14px;">Đang tải...</span>
    </div>

    {{-- TIÊU CHÍ --}}
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:20px;margin-bottom:18px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <h3 style="margin:0;font-size:15px;">Tiêu chí đánh giá</h3>
            <button type="button" id="btnApplySuggest" class="btn"
                    style="background:#dbeafe;color:#1d4ed8;font-size:13px;{{ $suggested ? '' : 'display:none;' }}"
                    onclick="applySuggested()">✨ Áp dụng điểm đề xuất</button>
        </div>

        @php
        $criteria = [
            ['key'=>'punctuality',    'label'=>'Đi đúng giờ',         'max'=>10,  'color'=>'#0ea5e9'],
            ['key'=>'task_completion','label'=>'Hoàn thành công việc', 'max'=>30,  'color'=>'#8b5cf6'],
            ['key'=>'quality',        'label'=>'Chất lượng công việc', 'max'=>20,  'color'=>'#10b981'],
            ['key'=>'technical_skill','label'=>'Kỹ năng chuyên môn',   'max'=>10,  'color'=>'#f59e0b'],
            ['key'=>'responsibility', 'label'=>'Trách nhiệm',          'max'=>10,  'color'=>'#ef4444'],
            ['key'=>'teamwork',       'label'=>'Làm việc nhóm',        'max'=>10,  'color'=>'#ec4899'],
            ['key'=>'attitude',       'label'=>'Thái độ',              'max'=>10,  'color'=>'#14b8a6'],
        ];
        @endphp

        @foreach($criteria as $c)
        <div style="display:grid;grid-template-columns:150px 1fr 60px 60px;align-items:center;gap:10px;margin-bottom:12px;">
            <label style="margin:0;font-size:13px;color:#475569;">{{ $c['label'] }}</label>
            <input type="range" id="range_{{ $c['key'] }}" min="0" max="{{ $c['max'] }}"
                   value="{{ old($c['key'], $evaluation->{$c['key']} ?? 0) }}"
                   style="accent-color:{{ $c['color'] }}"
                   oninput="syncScore('{{ $c['key'] }}',this.value,{{ $c['max'] }})">
            <input type="number" id="num_{{ $c['key'] }}" name="{{ $c['key'] }}"
                   min="0" max="{{ $c['max'] }}"
                   value="{{ old($c['key'], $evaluation->{$c['key']} ?? 0) }}"
                   style="text-align:center;padding:6px 4px;border:1px solid #cbd5e1;border-radius:6px;font:inherit;"
                   oninput="syncScore('{{ $c['key'] }}',this.value,{{ $c['max'] }},true)" required>
            <span style="color:#94a3b8;font-size:13px;">/{{ $c['max'] }}</span>
        </div>
        @endforeach

        <div style="margin-top:14px;padding-top:12px;border-top:1px solid #e2e8f0;display:flex;align-items:center;gap:12px;">
            <span style="font-weight:700;">Tổng:</span>
            <span id="scoreDisplay" style="font-size:28px;font-weight:900;color:#2563eb;">0</span>
            <span style="color:#64748b;">/100</span>
            <span id="classDisplay" style="padding:4px 12px;border-radius:999px;font-size:13px;font-weight:700;"></span>
        </div>
    </div>

    <div class="field">
        <label>Đánh giá chung (1–5) <span style="color:#dc2626">*</span></label>
        <select id="sel_rating" name="rating" required>
            @for($i=1;$i<=5;$i++)
            <option value="{{ $i }}" {{ old('rating',$evaluation->rating)==$i?'selected':'' }}>
                {{ $i }} / 5 — {{ ['','Kém','Yếu','Trung bình','Tốt','Xuất sắc'][$i] }}
            </option>
            @endfor
        </select>
    </div>

    <div class="field">
        <label>Tóm tắt</label>
        <textarea id="ta_summary" name="summary" rows="3"
            style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px;font:inherit;resize:vertical;">{{ old('summary',$evaluation->summary) }}</textarea>
    </div>

    <div class="field">
        <label>Nhận xét</label>
        <textarea id="ta_comments" name="comments" rows="3"
            style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px;font:inherit;resize:vertical;">{{ old('comments',$evaluation->comments) }}</textarea>
    </div>

    <div style="display:flex;gap:12px;margin-top:20px;">
        <button type="submit" class="btn primary">{{ $evaluation->exists ? 'Lưu thay đổi' : 'Tạo đánh giá' }}</button>
        <a href="{{ route('evaluations.index') }}" class="btn">Hủy</a>
    </div>
</form>
</div>
</div>{{-- /left --}}

{{-- ============================================================
     RIGHT: PANEL THỰC TẾ
============================================================ --}}
<div id="statsPanel">
    <div id="statsLoading" style="display:none;text-align:center;padding:40px;color:#64748b;">
        <div style="font-size:36px;margin-bottom:8px;">⏳</div>Đang tải dữ liệu thực tế...
    </div>
    <div id="statsContent">
        @if($monthlyStats)
            @include('hr.evaluations._stats_panel', ['stats' => $monthlyStats, 'suggested' => $suggested])
        @else
        <div class="card" style="padding:36px;text-align:center;color:#94a3b8;">
            <div style="font-size:44px;margin-bottom:10px;">📊</div>
            <p style="margin:0;line-height:1.6;">Chọn nhân viên và tháng,<br>dữ liệu thực tế sẽ tự động hiện ra.</p>
        </div>
        @endif
    </div>
</div>

</div>{{-- /grid --}}
</div>{{-- /max-width --}}

@push('scripts')
<script>
const SUGGEST_URL = '{{ route('evaluations.suggest') }}';
const criteria    = ['punctuality','task_completion','quality','technical_skill','responsibility','teamwork','attitude'];
const maxes       = {punctuality:10,task_completion:30,quality:20,technical_skill:10,responsibility:10,teamwork:10,attitude:10};
let serverSuggested = @json($suggested ?? null);

function syncScore(key, val, max, fromNum) {
    val = Math.min(max, Math.max(0, parseInt(val) || 0));
    document.getElementById('range_' + key).value = val;
    document.getElementById('num_'   + key).value = val;
    updateTotal();
}

function updateTotal() {
    let total = 0;
    criteria.forEach(k => { total += parseInt(document.getElementById('num_'+k).value) || 0; });
    document.getElementById('scoreDisplay').textContent = total;
    const cls = total>=85?'Xuất sắc':total>=70?'Tốt':total>=50?'Trung bình':'Yếu';
    const map  = {'Xuất sắc':'background:#dcfce7;color:#166534','Tốt':'background:#dbeafe;color:#1d4ed8',
                  'Trung bình':'background:#fef9c3;color:#854d0e','Yếu':'background:#fee2e2;color:#dc2626'};
    const el = document.getElementById('classDisplay');
    el.textContent = cls;
    el.style.cssText = `padding:4px 12px;border-radius:999px;font-size:13px;font-weight:700;${map[cls]}`;
}

function applySuggested() {
    if (!serverSuggested) return;
    criteria.forEach(k => syncScore(k, serverSuggested[k] || 0, maxes[k]));
    document.getElementById('sel_rating').value = serverSuggested.rating || 3;
    if (serverSuggested.summary)  document.getElementById('ta_summary').value  = serverSuggested.summary;
    if (serverSuggested.comments) document.getElementById('ta_comments').value = serverSuggested.comments;
    updateTotal();
}

function onParamChange() {
    const empId = document.getElementById('sel_employee').value;
    const month = document.getElementById('sel_month').value;
    if (empId && month) fetchSuggest();
}

function fetchSuggest() {
    const empId = document.getElementById('sel_employee').value;
    const month = document.getElementById('sel_month').value;
    if (!empId || !month) { return; }

    document.getElementById('statsLoading').style.display  = 'block';
    document.getElementById('statsContent').style.display  = 'none';
    document.getElementById('loadSpinner').style.display   = 'inline';
    document.getElementById('loadBtnWrap').style.display   = 'none';

    fetch(`${SUGGEST_URL}?employee_id=${empId}&month=${encodeURIComponent(month)}`, {
        headers: {'Accept':'application/json',
                  'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content}
    })
    .then(r => r.json())
    .then(data => {
        serverSuggested = data.suggested;
        renderPanel(data.stats, data.suggested);
        document.getElementById('btnApplySuggest').style.display = '';
        document.getElementById('statsLoading').style.display    = 'none';
        document.getElementById('statsContent').style.display    = 'block';
        document.getElementById('loadSpinner').style.display     = 'none';
    })
    .catch(() => {
        document.getElementById('statsLoading').style.display = 'none';
        document.getElementById('statsContent').innerHTML =
            '<div class="card" style="padding:20px;color:#dc2626;">Lỗi tải dữ liệu. Thử lại.</div>';
        document.getElementById('statsContent').style.display = 'block';
    });
}

function badge(text, bg, color) {
    return `<span style="padding:3px 9px;border-radius:999px;font-size:12px;font-weight:700;background:${bg};color:${color};display:inline-block;margin:2px;">${text}</span>`;
}

function renderPanel(stats, suggested) {
    const att = stats.attendance, lv = stats.leave, ot = stats.overtime, pay = stats.payroll;
    const fmt = n => new Intl.NumberFormat('vi-VN').format(n);
    const clsMap = {'Xuất sắc':'#16a34a','Tốt':'#2563eb','Trung bình':'#d97706','Yếu':'#dc2626'};
    const cc = clsMap[suggested.classification] || '#64748b';

    let h = '<div style="display:flex;flex-direction:column;gap:14px;">';

    // Chấm công
    h += `<div class="card" style="padding:16px;">
        <div style="font-weight:700;margin-bottom:10px;font-size:14px;">📅 Chấm công tháng</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px;">
            <div style="background:#f0fdf4;border-radius:8px;padding:10px;text-align:center;">
                <div style="font-size:22px;font-weight:800;color:#16a34a;">${att.present_days}</div><div style="font-size:12px;color:#64748b;">Đúng giờ</div></div>
            <div style="background:#fef3c7;border-radius:8px;padding:10px;text-align:center;">
                <div style="font-size:22px;font-weight:800;color:#d97706;">${att.late_days}</div><div style="font-size:12px;color:#64748b;">Đi muộn</div></div>
            <div style="background:#fee2e2;border-radius:8px;padding:10px;text-align:center;">
                <div style="font-size:22px;font-weight:800;color:#dc2626;">${att.absent_days}</div><div style="font-size:12px;color:#64748b;">Vắng mặt</div></div>
            <div style="background:#ede9fe;border-radius:8px;padding:10px;text-align:center;">
                <div style="font-size:22px;font-weight:800;color:#7c3aed;">${att.overtime_days}</div><div style="font-size:12px;color:#64748b;">Có OT</div></div>
        </div>
        <div style="font-size:13px;color:#475569;">⏱ TB <strong>${att.avg_work_hours}h/ngày</strong> · ⏰ Muộn <strong>${att.total_late_min} phút</strong> · 📋 ${att.total_records} bản ghi</div>
    </div>`;

    // Nghỉ phép
    h += `<div class="card" style="padding:16px;">
        <div style="font-weight:700;margin-bottom:8px;font-size:14px;">🏖 Nghỉ phép</div>
        <div style="margin-bottom:8px;">${badge('✅ Duyệt: '+lv.approved_count+' đơn ('+lv.approved_days+'ng)','#dcfce7','#166534')}
        ${lv.pending_count>0?badge('⏳ Chờ: '+lv.pending_count,'#fef9c3','#854d0e'):''}
        ${lv.urgent_count>0?badge('⚡ Đột xuất: '+lv.urgent_count,'#fee2e2','#dc2626'):''}</div>`;
    (lv.list||[]).slice(0,4).forEach(l => {
        const lc = l.status==='approved'?'#dcfce7':l.status==='pending'?'#fef9c3':'#fee2e2';
        h += `<div style="font-size:12px;padding:4px 0;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;">
            <span>${l.start}→${l.end} · ${l.days}ng${l.urgent?' ⚡':''}</span>
            <span style="background:${lc};padding:1px 7px;border-radius:999px;">${l.status}</span></div>`;
    });
    h += '</div>';

    // Tăng ca
    h += `<div class="card" style="padding:16px;">
        <div style="font-weight:700;margin-bottom:8px;font-size:14px;">⏰ Tăng ca</div>
        ${badge('✅ Duyệt: '+ot.approved_count,'#dcfce7','#166534')}
        ${ot.pending_count>0?badge('⏳ Chờ: '+ot.pending_count,'#fef9c3','#854d0e'):''}
    </div>`;

    // Lương
    if (pay && pay.exists) {
        h += `<div class="card" style="padding:16px;">
            <div style="font-weight:700;margin-bottom:8px;font-size:14px;">💰 Lương tháng</div>
            <div style="font-size:13px;display:grid;grid-template-columns:1fr 1fr;gap:3px;">
                <span style="color:#64748b;">Cơ bản</span><span style="text-align:right;font-weight:600;">${fmt(pay.base_salary)}₫</span>
                <span style="color:#64748b;">Phụ cấp</span><span style="text-align:right;font-weight:600;">${fmt(pay.allowance)}₫</span>
                <span style="color:#dc2626;">Khấu trừ</span><span style="text-align:right;font-weight:600;color:#dc2626;">${fmt(pay.deduction)}₫</span>
                <span style="color:#16a34a;font-weight:700;border-top:1px solid #e2e8f0;padding-top:5px;">Thực lĩnh</span>
                <span style="text-align:right;font-weight:800;color:#16a34a;border-top:1px solid #e2e8f0;padding-top:5px;">${fmt(pay.total_salary)}₫</span>
            </div></div>`;
    }

    // Đề xuất
    h += `<div class="card" style="padding:16px;border:2px solid ${cc}44;background:${cc}0d;">
        <div style="font-weight:700;margin-bottom:10px;font-size:14px;">✨ Đề xuất hệ thống</div>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
            <div style="font-size:38px;font-weight:900;color:${cc};line-height:1;">${suggested.score_total}</div>
            <div><div style="font-size:11px;color:#64748b;">/100 điểm</div>
            <span style="padding:3px 12px;border-radius:999px;font-size:13px;font-weight:700;color:#fff;background:${cc};">${suggested.classification}</span></div>
        </div>
        <div style="font-size:12px;color:#475569;display:grid;grid-template-columns:1fr 1fr;gap:3px;">
            <span>Đúng giờ: <strong>${suggested.punctuality}/10</strong></span>
            <span>Hoàn thành: <strong>${suggested.task_completion}/30</strong></span>
            <span>Chất lượng: <strong>${suggested.quality}/20</strong></span>
            <span>Chuyên môn: <strong>${suggested.technical_skill}/10</strong></span>
            <span>Trách nhiệm: <strong>${suggested.responsibility}/10</strong></span>
            <span>Nhóm: <strong>${suggested.teamwork}/10</strong></span>
            <span>Thái độ: <strong>${suggested.attitude}/10</strong></span>
        </div>
        <button type="button" onclick="applySuggested()"
            style="margin-top:12px;width:100%;padding:9px;background:${cc};color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:14px;">
            Áp dụng điểm đề xuất này
        </button>
    </div>`;

    h += '</div>';
    document.getElementById('statsContent').innerHTML = h;
}

document.addEventListener('DOMContentLoaded', () => {
    updateTotal();
    if (serverSuggested) document.getElementById('btnApplySuggest').style.display = '';
});
</script>
@endpush
@endsection
