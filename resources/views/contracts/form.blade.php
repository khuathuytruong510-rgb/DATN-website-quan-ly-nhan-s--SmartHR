@extends('layouts.app')

@section('content')
<div class="contract-page">
<div class="page-head">
    <div>
        @if(isset($renewingFrom))
            <h1>Gia hạn hợp đồng</h1>
        @elseif($isEdit)
            <h1>Chỉnh sửa hợp đồng</h1>
        @else
            <h1>Tạo hợp đồng mới</h1>
        @endif
    </div>
    <a class="btn link" href="{{ route('contracts.index') }}">Quay lại</a>
</div>

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
<div id="formMessages"></div>

{{-- Banner gia hạn --}}
@if(isset($renewingFrom))
<div style="background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:10px;padding:14px 18px;margin-bottom:18px;display:flex;gap:16px;align-items:flex-start;">
    <div style="font-size:26px;line-height:1;">🔄</div>
    <div>
        <div style="font-weight:700;color:#1d4ed8;margin-bottom:4px;">Gia hạn từ hợp đồng {{ $renewingFrom->contract_code }}</div>
        <div style="font-size:13px;color:#3b82f6;">
            Hiệu lực: {{ optional($renewingFrom->start_date)->format('d/m/Y') }} →
            {{ $renewingFrom->end_date ? optional($renewingFrom->end_date)->format('d/m/Y') : 'Không XĐ' }}
            &nbsp;|&nbsp; Loại: {{ ['internship'=>'Thực tập','probation'=>'Thử việc','fixed_term'=>'Xác định TH','indefinite'=>'Không XĐ TH','official'=>'Chính thức','seasonal'=>'Thời vụ'][$renewingFrom->contract_type] ?? $renewingFrom->contract_type }}
        </div>
        @if(isset($latestPayroll) && $latestPayroll)
            @php $lDiff = (float)($latestPayroll->base_salary ?? 0) - (float)($renewingFrom->base_salary ?? $renewingFrom->salary ?? 0); @endphp
            <div style="margin-top:8px;font-size:13px;color:{{ abs($lDiff)>0 ? '#92400e' : '#166534' }};background:{{ abs($lDiff)>0 ? '#fef9c3' : '#dcfce7' }};border-radius:6px;padding:6px 10px;display:inline-block;">
                @if(abs($lDiff) > 0)
                    ⚠️ Bảng lương T{{ $latestPayroll->month }}/{{ $latestPayroll->year }} khác hợp đồng cũ:
                    <strong>{{ number_format($latestPayroll->base_salary,0,',','.') }}₫</strong>
                    ({{ $lDiff > 0 ? '+' : '' }}{{ number_format($lDiff,0,',','.') }}₫)
                    — Lương bên dưới đã được điền theo bảng lương mới nhất.
                @else
                    ✓ Lương bảng lương T{{ $latestPayroll->month }}/{{ $latestPayroll->year }} khớp với hợp đồng cũ.
                @endif
            </div>
        @endif
    </div>
</div>
@endif

<form method="POST"
      action="{{ isset($renewingFrom) ? route('contracts.storeRenewal', $renewingFrom) : ($isEdit ? route('contracts.update', $contract) : route('contracts.store')) }}"
      enctype="multipart/form-data" id="contractForm">
    @csrf
    @if($isEdit) @method('PUT') @endif
    @if(isset($renewingFrom))
        <input type="hidden" name="parent_contract_id" value="{{ $renewingFrom->id }}">
    @endif

    <div style="display:flex;flex-direction:column;gap:16px;width:100%;">
    <div style="flex:1;min-width:0;overflow-y:auto;padding-right:6px;">

    {{-- 1. Thông tin nhân viên --}}
    <div class="card" style="margin-bottom:20px;">
        <div style="padding:6px 0 16px 0;border-bottom:1px solid var(--line);margin-bottom:16px;">
            <strong>1. Thông tin nhân viên</strong>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            @if(isset($renewingFrom))
                <input type="hidden" name="employee_id" value="{{ $contract->employee_id }}">
                <div class="field">
                    <label>Mã nhân viên</label>
                    <input type="text" readonly style="background:#f8fafc;color:#64748b;" value="{{ optional($contract->employee)->employee_code ?? '' }}">
                </div>
                <div class="field">
                    <label>Họ tên</label>
                    <input type="text" readonly value="{{ optional($contract->employee)->name ?? '' }}">
                </div>
            @elseif($isEdit)
                <input type="hidden" name="employee_id" value="{{ $contract->employee_id }}">
                <div class="field">
                    <label>Mã nhân viên</label>
                    <input type="text" id="employeeCode" readonly style="background:#f8fafc;color:#64748b;" value="{{ optional($contract->employee)->employee_code ?? '' }}">
                </div>
                <div class="field">
                    <label>Họ tên</label>
                    <input type="text" id="employeeName" readonly value="{{ optional($contract->employee)->name ?? '' }}">
                </div>
            @else
                <div class="field">
                    <label>Mã nhân viên <span class="text-danger">*</span></label>
                    <div style="display:flex;gap:8px;">
                        <input type="text" id="employeeCode" value="{{ old('employee_code', optional($contract->employee)->employee_code ?? '') }}" placeholder="VD: NS-0001" autocomplete="off" required style="flex:1;">
                        <button type="button" class="btn" id="lookupEmployeeBtn">Tìm</button>
                    </div>
                    <input type="hidden" name="employee_id" id="employeeId" value="{{ old('employee_id', $contract->employee_id) }}" required>
                    @error('employee_id')<span class="error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label>Họ tên</label>
                    <input type="text" id="employeeName" readonly value="{{ optional($contract->employee)->name ?? '' }}" style="background:#f8fafc;color:#475569;">
                </div>
            @endif
            <div class="field">
                <label>Phòng ban</label>
                <input type="text" id="employeeDepartment" readonly style="background:#f8fafc;color:#475569;" value="{{ optional(optional($contract->employee)->department)->name ?? '' }}">
            </div>
            <div class="field">
                <label>Chức vụ</label>
                <input type="text" id="employeePosition" readonly style="background:#f8fafc;color:#475569;" value="{{ optional($contract->employee)->position ?? '' }}">
            </div>
            <div class="field" style="grid-column:1 / -1;">
                <label>Email nhân viên <span class="text-danger">*</span></label>
                <input type="email" name="employee_email" id="employeeEmail" required
                       value="{{ old('employee_email', optional($contract->employee)->email ?? '') }}"
                       placeholder="email@congty.com">
                @error('employee_email')<span class="error">{{ $message }}</span>@enderror
            </div>
        </div>
    </div>

    {{-- 2. Thông tin hợp đồng --}}
    <div class="card" style="margin-bottom:20px;">
        <div style="padding:6px 0 16px 0;border-bottom:1px solid var(--line);margin-bottom:16px;">
            <strong>2. Thông tin hợp đồng</strong>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="field">
                <label>Mã hợp đồng</label>
                @if($isEdit)
                    <input type="text" name="contract_code" value="{{ old('contract_code', $contract->contract_code ?? '') }}" readonly style="background:#f8fafc;color:#475569;">
                @else
                    <input type="text" value="{{ old('contract_code', $contract->contract_code ?? '') }}" readonly style="background:#f8fafc;color:#475569;" placeholder="Hệ thống tự sinh khi lưu">
                @endif
                @error('contract_code')<span class="error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label>Loại hợp đồng <span class="text-danger">*</span></label>
                <select name="contract_type" id="contractTypeSelect" required>
                    <option value="">-- Chọn loại hợp đồng --</option>
                    <option value="probation"   {{ old('contract_type', $contract->contract_type) == 'probation'   ? 'selected' : '' }}>Hợp đồng thử việc</option>
                    <option value="fixed_term"  {{ old('contract_type', $contract->contract_type) == 'fixed_term'  ? 'selected' : '' }}>Hợp đồng lao động xác định thời hạn</option>
                    <option value="indefinite"  {{ old('contract_type', $contract->contract_type) == 'indefinite'  ? 'selected' : '' }}>Hợp đồng lao động không xác định thời hạn</option>
                    <option value="internship"  {{ old('contract_type', $contract->contract_type) == 'internship'  ? 'selected' : '' }}>Hợp đồng thực tập</option>
                    <option value="seasonal"    {{ old('contract_type', $contract->contract_type) == 'seasonal'    ? 'selected' : '' }}>Hợp đồng thời vụ</option>
                    @if(old('contract_type', $contract->contract_type ?? '') === 'official')
                        <option value="official" selected>Hợp đồng lao động chính thức (dữ liệu cũ — chọn loại mới khi sửa)</option>
                    @endif
                </select>
                @error('contract_type')<span class="error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label>Ngày bắt đầu <span class="text-danger">*</span></label>
                <input type="date" name="start_date" value="{{ old('start_date', optional($contract->start_date)->format('Y-m-d')) }}" required>
                @error('start_date')<span class="error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label>Ngày kết thúc</label>
                <input type="date" name="end_date" id="endDateInput" value="{{ old('end_date', optional($contract->end_date)->format('Y-m-d')) }}">
                @error('end_date')<span class="error">{{ $message }}</span>@enderror
                <label style="font-weight:400;margin-top:6px;">
                    <input type="checkbox" id="noEndDate" {{ !$contract->end_date ? 'checked' : '' }}>
                    Không xác định thời hạn
                </label>
            </div>
            <div class="field">
                <label>Trạng thái</label>
                @php
                    $previewStatus = $isEdit ? ($contract->status ?? 'draft') : 'draft';
                    $previewLabel = $isEdit && $contract->statusLabel ? $contract->statusLabel() : 'Nháp — HR đang soạn';
                    $previewBadge = in_array($previewStatus, ['active', 'signed'], true) ? 'success' : 'secondary';
                @endphp
                <div style="padding:10px 12px;background:#f8fafc;border:1px solid var(--line);border-radius:8px;">
                    <span class="badge {{ $previewBadge }}" style="font-size:11px;">{{ $previewLabel }}</span>
                </div>
            </div>
            <input type="hidden" name="title" id="contractTitleInput" value="{{ old('title', $contract->title ?? '') }}">
        </div>
    </div>

    {{-- 3. Thông tin lương --}}
    <div class="card" style="margin-bottom:20px;">
        <div style="padding:6px 0 16px 0;border-bottom:1px solid var(--line);margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;">
            <strong>3. Thông tin lương</strong>
            @if(isset($latestPayroll) && $latestPayroll)
                <button type="button" id="btnFillFromPayroll" class="btn" style="font-size:12px;padding:4px 10px;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;">
                    📋 Lấy từ BL T{{ $latestPayroll->month }}/{{ $latestPayroll->year }}
                </button>
            @endif
        </div>
        <div id="salaryAlert" style="display:none;padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:14px;background:#fffbeb;color:#92400e;border:1px solid #fde68a;">
            <span id="salaryAlertText"></span>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="field">
                <label>Lương cơ bản <span class="text-danger">*</span></label>
                <input type="text" id="baseSalaryDisplay" value="{{ number_format(old('base_salary', $contract->salary ?? $contract->base_salary ?? 0), 0, ',', '.') }}" placeholder="Nhập lương cơ bản">
                <input type="hidden" name="base_salary" id="baseSalaryInput" value="{{ old('base_salary', $contract->salary ?? $contract->base_salary ?? 0) }}">
                @error('base_salary')<span class="error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label>Phụ cấp chức vụ</label>
                <input type="text" id="allowanceDisplay" value="{{ number_format(old('allowance', $contract->allowance ?? 0), 0, ',', '.') }}" placeholder="0">
                <input type="hidden" name="allowance" id="allowanceInput" value="{{ old('allowance', $contract->allowance ?? 0) }}">
            </div>
            <div class="field">
                <label>Phụ cấp khác</label>
                <input type="text" id="bonusDisplay" value="{{ old('bonus', $contract->bonus ?? 0) > 0 ? number_format(old('bonus', $contract->bonus ?? 0), 0, ',', '.') : '' }}" placeholder="0">
                <input type="hidden" name="bonus" id="bonusInput" value="{{ old('bonus', $contract->bonus ?? 0) }}">
            </div>
            <div class="field">
                <label>Hình thức thanh toán</label>
                <div style="padding:10px 12px;background:#f8fafc;border:1px solid var(--line);border-radius:8px;">
                    Tiền mặt và chuyển khoản
                </div>
            </div>
            <div class="field">
                <label>Nơi làm việc</label>
                <input type="text" name="workplace" value="{{ old('workplace', $contract->workplace) }}">
            </div>
            <div class="field">
                <label>Phúc lợi</label>
                <textarea name="benefits" rows="3">{{ old('benefits', $contract->benefits) }}</textarea>
            </div>
            <div class="field">
                <label>Tổng thu nhập</label>
                <input type="text" id="totalIncomeDisplay" readonly style="font-weight:700;background:#f0fdf4;color:#16a34a;" value="" aria-readonly="true">
            </div>
        </div>
    </div>

    {{-- 4. Điều khoản hợp đồng (mẫu hệ thống) --}}
    <div class="card" style="margin-bottom:20px;">
        <div style="padding:6px 0 16px 0;border-bottom:1px solid var(--line);margin-bottom:16px;">
            <strong>4. Điều khoản hợp đồng</strong>
        </div>
        <div id="templateAlert" style="display:none;padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:14px;background:#fffbeb;color:#92400e;border:1px solid #fde68a;">
            <span id="templateAlertText"></span>
        </div>
        <div class="field">
            <label>Nội dung điều khoản</label>
            <div id="templateLoader" style="display:none;margin-bottom:8px;">
                <span style="display:inline-block;width:14px;height:14px;border:2px solid #e2e8f0;border-top-color:#2563eb;border-radius:50%;animation:spin .6s linear infinite;margin-right:6px;vertical-align:middle;"></span>
                <span class="muted">Đang tải mẫu điều khoản...</span>
            </div>
            @php $officialTerms = old('contract_content', $contract->contract_content ?? $contract->terms ?? ''); @endphp
            <pre id="contractContentPreview" style="white-space:pre-wrap;background:#f8fafc;border:1px solid var(--line);border-radius:8px;padding:14px 16px;max-height:420px;overflow:auto;font-family:inherit;line-height:1.7;margin:0;">{{ $officialTerms !== '' ? $officialTerms : 'Chọn loại hợp đồng để hiển thị điều khoản mẫu.' }}</pre>
            <input type="hidden" name="contract_content" id="contractContentField" value="{{ $officialTerms }}">
        </div>
    </div>

    {{-- 5. File hợp đồng --}}
    <div class="card" style="margin-bottom:20px;">
        <div style="padding:6px 0 16px 0;border-bottom:1px solid var(--line);margin-bottom:16px;">
            <strong>5. File hợp đồng</strong>
        </div>
        <div class="field">
            <input type="file" name="document" accept=".pdf,.doc,.docx">
        </div>
        @error('document')<span class="error">{{ $message }}</span>@enderror
        @if($contract->document_name)
            <div style="margin-top:8px;">
                <span class="muted">File hiện tại:</span> <strong>{{ $contract->document_name }}</strong>
                @if($contract->document_path)
                    <a class="btn link" href="{{ Storage::url($contract->document_path) }}" target="_blank">Xem</a>
                @endif
            </div>
        @endif
    </div>

    {{-- 6. Ghi chú nội bộ --}}
    <div class="card" style="margin-bottom:20px;">
        <div style="padding:6px 0 16px 0;border-bottom:1px solid var(--line);margin-bottom:16px;">
            <strong>6. Ghi chú nội bộ</strong>
        </div>
        <div class="field">
            <textarea name="notes" rows="4" placeholder="Ghi chú HR nội bộ — không đưa vào văn bản hợp đồng nhân viên ký.">{{ old('notes', $contract->notes) }}</textarea>
        </div>
    </div>

    <div class="actions" style="margin-top:0;margin-bottom:0;">
        <button class="btn primary" type="submit">{{ isset($renewingFrom) ? '🔄 Tạo hợp đồng gia hạn' : 'Lưu nháp' }}</button>
        @if(!isset($renewingFrom))
            <button class="btn" type="button" id="exportContractButton" title="Chỉ tải bản xem trước trên máy — không lưu DB, không phải hợp đồng đã ký">Xuất file xem trước</button>
        @endif
        <a class="btn" href="{{ route('contracts.index') }}">Hủy</a>
    </div>

    </div>{{-- end scroll wrapper --}}
    </div>{{-- end flex --}}
</form>

{{-- Quick Info sidebar --}}
<div class="card" style="margin-top:16px;">
    <div style="padding:6px 0 14px 0;border-bottom:1px solid var(--line);margin-bottom:14px;">
        <strong>Thông tin nhanh</strong>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:14px;">
            <div class="muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.08em;">Tổng thu nhập</div>
            <div style="font-size:22px;font-weight:800;margin-top:6px;" id="qiHighlightTotalIncome">0 VNĐ</div>
        </div>
        <div>
            <div class="muted" style="font-size:11px;">Loại hợp đồng</div>
            <div style="font-weight:600;margin-top:4px;" id="qiContractType">—</div>
        </div>
        <div>
            <div class="muted" style="font-size:11px;">Lương cơ bản</div>
            <div style="font-weight:600;margin-top:4px;" id="qiBaseSalary">0 VNĐ</div>
        </div>
        <div>
            <div class="muted" style="font-size:11px;">Phụ cấp</div>
            <div style="font-weight:600;margin-top:4px;" id="qiAllowance">0 VNĐ</div>
        </div>
        <div>
            <div class="muted" style="font-size:11px;">Trạng thái (preview)</div>
            <div style="margin-top:4px;">
                @php
                    $b = match($contract->status ?? 'draft') {
                        'waiting_employee_signature','waiting_director_signature','pending_signature','director_signed','employee_signed','draft' => 'warning',
                        'signed', 'active' => 'success',
                        'expiring'  => 'info',
                        'expired'   => 'danger',
                        'cancelled' => 'secondary',
                        default     => 'secondary',
                    };
                    $lbl = $isEdit && method_exists($contract, 'statusLabel')
                        ? $contract->statusLabel()
                        : 'Nháp — HR đang soạn';
                @endphp
                <span class="badge {{ $b }}" style="font-size:10px;">{{ $lbl }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Người ký --}}
<div class="card" style="margin-top:16px;">
    <div style="padding:6px 0 14px 0;border-bottom:1px solid var(--line);margin-bottom:14px;">
        <strong>Người ký kết (theo quy trình)</strong>
    </div>
    <ul style="margin:0;padding-left:18px;font-size:14px;line-height:1.8;">
        <li><strong>Giám đốc</strong> — ký phía doanh nghiệp (sau HR gửi ký)</li>
        <li><strong>Nhân viên</strong> — ký phía người lao động (sau Giám đốc)</li>
    </ul>
</div>
</div>{{-- end contract-page --}}

<style>
@keyframes spin { to { transform: rotate(360deg); } }
.badge { display:inline-flex;border-radius:999px;padding:4px 8px;font-size:11px;font-weight:700; }
.badge.warning  { background:#fef3c7;color:#92400e; }
.badge.success  { background:#dcfce7;color:#166534; }
.badge.info     { background:#e0f2fe;color:#0369a1; }
.badge.danger   { background:#fee2e2;color:#dc2626; }
.badge.secondary{ background:#f1f5f9;color:#475569; }
</style>
@endsection

@push('scripts')
<script>
(function () {
    const fmt = n => (Number(n)||0).toLocaleString('vi-VN', {maximumFractionDigits:0});
    const parseFormatted = s => parseInt((s||'').replace(/\./g,'').replace(/,/g,'')) || 0;

    const $  = id => document.getElementById(id);
    const employeeCodeInput   = document.getElementById('employeeCode');
    const employeeIdInput     = document.getElementById('employeeId');
    const lookupEmployeeBtn   = document.getElementById('lookupEmployeeBtn');
    const employeeLookupHint  = document.getElementById('employeeLookupHint');
    const baseSalaryDisplay   = $('baseSalaryDisplay');
    const baseSalaryInput     = $('baseSalaryInput');
    const allowanceDisplay    = $('allowanceDisplay');
    const allowanceInput      = $('allowanceInput');
    const bonusDisplay        = $('bonusDisplay');
    const bonusInput          = $('bonusInput');
    const totalIncomeDisplay  = $('totalIncomeDisplay');
    const qiBaseSalary        = $('qiBaseSalary');
    const qiAllowance         = $('qiAllowance');
    const qiHighlightTotalIncome = $('qiHighlightTotalIncome');
    const qiContractType      = $('qiContractType');
    const contractTypeSelect  = $('contractTypeSelect');
    const contractTitleInput  = $('contractTitleInput');
    const contractContentField= $('contractContentField');
    const contractContentPreview = $('contractContentPreview');
    const templateLoader      = $('templateLoader');
    const templateAlert       = $('templateAlert');
    const templateAlertText   = $('templateAlertText');
    const salaryAlert         = $('salaryAlert');
    const salaryAlertText     = $('salaryAlertText');
    const noEndDate           = $('noEndDate');
    const endDateInput        = $('endDateInput');
    const exportBtn           = $('exportContractButton');
    const btnFillFromPayroll  = $('btnFillFromPayroll');
    const companyName         = @json('Công ty TNHH SmartHR');
    @php
        $exportDirector = \App\Models\User::query()->where('is_director', true)->with('employee')->orderBy('id')->first();
        $exportDirectorName = optional(optional($exportDirector)->employee)->name
            ?? optional($exportDirector)->name
            ?? 'Giám đốc';
    @endphp
    const directorName        = @json($exportDirectorName);

    const typeLabels  = {internship:'Thực tập',probation:'Thử việc',fixed_term:'XĐ thời hạn',indefinite:'Không XĐ TH',official:'LĐ chính thức (cũ)',seasonal:'Thời vụ'};
    const typeTitles  = {internship:'Hợp đồng thực tập',probation:'Hợp đồng thử việc',fixed_term:'Hợp đồng lao động xác định thời hạn',indefinite:'Hợp đồng lao động không xác định thời hạn',official:'Hợp đồng lao động chính thức',seasonal:'Hợp đồng thời vụ'};

    let previousContractType = contractTypeSelect?.value || '';

    function setOfficialTerms(content) {
        const text = (content || '').trim();
        if (contractContentField) contractContentField.value = text;
        if (contractContentPreview) {
            contractContentPreview.textContent = text || 'Chưa có mẫu điều khoản cho loại hợp đồng này.';
        }
    }

    // Payroll data injected from PHP (for renew page)
    const payrollBase      = {{ isset($latestPayroll) && $latestPayroll ? (float)($latestPayroll->base_salary ?? 0) : 'null' }};
    const payrollAllowance = {{ isset($latestPayroll) && $latestPayroll ? (float)($latestPayroll->allowance ?? 0) : 'null' }};

    function updateTotals() {
        const base      = Number(baseSalaryInput?.value)  || 0;
        const allowance = Number(allowanceInput?.value)   || 0;
        const bonus     = Number(bonusInput?.value)       || 0;
        const total     = base + allowance + bonus;
        if (totalIncomeDisplay)       totalIncomeDisplay.value       = fmt(total);
        if (qiBaseSalary)             qiBaseSalary.textContent       = fmt(base)      + ' VNĐ';
        if (qiAllowance)              qiAllowance.textContent        = fmt(allowance) + ' VNĐ';
        if (qiHighlightTotalIncome)   qiHighlightTotalIncome.textContent = fmt(total) + ' VNĐ';
    }

    function showAlert(el, textEl, msg) {
        if (!el || !textEl) return;
        el.style.display = msg ? 'block' : 'none';
        textEl.textContent = msg || '';
    }

    function attachNumberField(displayEl, hiddenEl) {
        if (!displayEl || !hiddenEl) return;
        displayEl.addEventListener('focus', () => { displayEl.value = hiddenEl.value || ''; });
        displayEl.addEventListener('input', () => {
            const num = parseFormatted(displayEl.value);
            hiddenEl.value = num;
            updateTotals();
        });
        displayEl.addEventListener('blur', () => {
            const num = Number(hiddenEl.value) || 0;
            displayEl.value = num > 0 ? fmt(num) : '';
        });
    }

    attachNumberField(baseSalaryDisplay, baseSalaryInput);
    attachNumberField(allowanceDisplay,  allowanceInput);
    attachNumberField(bonusDisplay,      bonusInput);

    // Fill from payroll button
    if (btnFillFromPayroll && payrollBase !== null) {
        btnFillFromPayroll.addEventListener('click', () => {
            if (baseSalaryInput)  { baseSalaryInput.value  = payrollBase;      baseSalaryDisplay.value  = fmt(payrollBase); }
            if (allowanceInput)   { allowanceInput.value   = payrollAllowance; allowanceDisplay.value   = fmt(payrollAllowance); }
            updateTotals();
            showAlert(salaryAlert, salaryAlertText, '');
        });
    }

    // Employee lookup by code
    const applyEmployeeData = (data) => {
        if (employeeIdInput) employeeIdInput.value = data.id || '';
        if (employeeCodeInput && data.employee_code) employeeCodeInput.value = data.employee_code;
        if (document.getElementById('employeeName')) document.getElementById('employeeName').value = data.name || '';
        if (document.getElementById('employeeDepartment')) document.getElementById('employeeDepartment').value = data.department?.name || '';
        if (document.getElementById('employeePosition')) document.getElementById('employeePosition').value = data.position || '';
        if (document.getElementById('employeeEmail')) document.getElementById('employeeEmail').value = data.email || '';
        const base = data.position_base_salary || 0;
        const allow = data.position_allowance || 0;
        if (base > 0 && baseSalaryInput) {
            baseSalaryInput.value = base; baseSalaryDisplay.value = fmt(base);
            allowanceInput.value = allow; allowanceDisplay.value = fmt(allow);
            updateTotals(); showAlert(salaryAlert, salaryAlertText, '');
        } else {
            showAlert(salaryAlert, salaryAlertText, 'Chưa cấu hình mức lương cho chức vụ này.');
            updateTotals();
        }
        if (employeeLookupHint) {
            employeeLookupHint.textContent = data.status_label
                ? `Đã tìm thấy: ${data.name} (${data.status_label}).`
                : `Đã tìm thấy: ${data.name}.`;
            employeeLookupHint.style.color = '';
        }
    };

    const clearEmployeeData = (message) => {
        if (employeeIdInput) employeeIdInput.value = '';
        if (document.getElementById('employeeName')) document.getElementById('employeeName').value = '';
        if (document.getElementById('employeeDepartment')) document.getElementById('employeeDepartment').value = '';
        if (document.getElementById('employeePosition')) document.getElementById('employeePosition').value = '';
        if (document.getElementById('employeeEmail') && !@json($isEdit)) document.getElementById('employeeEmail').value = '';
        if (employeeLookupHint) {
            employeeLookupHint.textContent = message || 'Nhập mã nhân viên rồi Enter hoặc bấm Tìm để điền thông tin.';
            employeeLookupHint.style.color = message ? '#b91c1c' : '';
        }
        updateTotals();
    };

    const lookupEmployeeByCode = () => {
        if (!employeeCodeInput || employeeCodeInput.readOnly) return;
        const code = (employeeCodeInput.value || '').trim();
        if (!code) {
            clearEmployeeData('Vui lòng nhập mã nhân viên.');
            return;
        }
        if (employeeLookupHint) {
            employeeLookupHint.textContent = 'Đang tìm...';
            employeeLookupHint.style.color = '';
        }
        fetch(@json(route('employees.by_code')) + '?code=' + encodeURIComponent(code), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(async (r) => {
                const data = await r.json().catch(() => ({}));
                if (!r.ok) throw new Error(data.message || 'Không tìm thấy nhân viên.');
                return data;
            })
            .then(applyEmployeeData)
            .catch((err) => clearEmployeeData(err.message || 'Không tìm thấy nhân viên.'));
    };

    lookupEmployeeBtn?.addEventListener('click', lookupEmployeeByCode);
    employeeCodeInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            lookupEmployeeByCode();
        }
    });
    employeeCodeInput?.addEventListener('input', () => {
        if (employeeIdInput) employeeIdInput.value = '';
    });
    employeeCodeInput?.addEventListener('blur', () => {
        if ((employeeCodeInput.value || '').trim() && !employeeIdInput?.value) {
            lookupEmployeeByCode();
        }
    });
    if (employeeIdInput?.value && employeeCodeInput && !employeeCodeInput.readOnly) {
        lookupEmployeeByCode();
    }

    // No end date checkbox
    noEndDate?.addEventListener('change', function () {
        if (endDateInput) { endDateInput.disabled = this.checked; if (this.checked) endDateInput.value = ''; }
    });
    if (noEndDate?.checked && endDateInput) endDateInput.disabled = true;

    // Contract type → title + template content
    const populateTemplateContent = (ct) => {
        if (!ct) return;
        previousContractType = ct;
        if (templateLoader) templateLoader.style.display = 'block';
        showAlert(templateAlert, templateAlertText, '');
        fetch(`/contract-templates/content?contract_type=${encodeURIComponent(ct)}`, { headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'} })
            .then(r => r.json())
            .then(data => {
                const content = (data.content || '').trim();
                setOfficialTerms(content);
                if (!content) {
                    showAlert(templateAlert, templateAlertText, `Chưa cấu hình mẫu hợp đồng cho loại "${typeLabels[ct]||ct}".`);
                }
            })
            .catch(() => showAlert(templateAlert, templateAlertText, 'Không thể tải mẫu hợp đồng.'))
            .finally(() => { if (templateLoader) templateLoader.style.display = 'none'; });
    };

    contractTypeSelect?.addEventListener('change', function () {
        const ct = this.value;
        if (qiContractType) qiContractType.textContent = typeLabels[ct] || (ct ? ct.replace(/_/g,' ') : '—');
        if (contractTitleInput) contractTitleInput.value = typeTitles[ct] || '';
        populateTemplateContent(ct);
    });

    // Export — Word HTML (bản xem trước, chưa ký)
    const escapeHtml = s => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    const formatDateVi = iso => {
        if (!iso) return '…/…/……';
        const p = iso.split('-');
        return p.length === 3 ? `${p[2]}/${p[1]}/${p[0]}` : iso;
    };
    const todayVi = () => {
        const d = new Date();
        return `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}/${d.getFullYear()}`;
    };
    const formatTermsHtml = text => {
        const blocks = String(text || '').trim().split(/\n{2,}/);
        return blocks.map(block => {
            const lines = block.split('\n').map(l => l.trim()).filter(Boolean);
            if (!lines.length) return '';
            const head = lines[0];
            if (/^ĐIỀU KHOẢN/i.test(head)) {
                return `<p class="section-title">${escapeHtml(head)}</p>` +
                    lines.slice(1).map(l => `<p class="clause">${escapeHtml(l)}</p>`).join('');
            }
            return lines.map(l => `<p class="clause">${escapeHtml(l)}</p>`).join('');
        }).join('');
    };
    const buildContractDocumentHtml = () => {
        const ct = contractTypeSelect?.value || '';
        const title = typeTitles[ct] || contractTitleInput?.value || 'HỢP ĐỒNG LAO ĐỘNG';
        const codeEl = document.querySelector('[name="contract_code"]');
        const code = (codeEl?.value || '').trim() || '(tự sinh khi lưu nháp)';
        const empName = document.getElementById('employeeName')?.value || '…………………………';
        const empCode = document.getElementById('employeeCode')?.value || '—';
        const dept = document.getElementById('employeeDepartment')?.value || '—';
        const position = document.getElementById('employeePosition')?.value || '—';
        const startDate = document.querySelector('[name="start_date"]')?.value || '';
        const endDate = noEndDate?.checked ? '' : (document.querySelector('[name="end_date"]')?.value || '');
        const workplace = document.querySelector('[name="workplace"]')?.value || 'Theo quy định công ty';
        const base = Number(baseSalaryInput?.value) || 0;
        const allowance = Number(allowanceInput?.value) || 0;
        const bonus = Number(bonusInput?.value) || 0;
        const total = base + allowance + bonus;
        const terms = contractContentField?.value.trim() || '';
        const endLabel = endDate ? formatDateVi(endDate) : 'Không xác định thời hạn';

        return `<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" lang="vi">
<head>
<meta charset="utf-8">
<title>${escapeHtml(code)} — ${escapeHtml(title)}</title>
<!--[if gte mso 9]><xml><w:WordDocument><w:View>Print</w:View><w:Zoom>100</w:Zoom></w:WordDocument></xml><![endif]-->
<style>
@page { size: A4; margin: 2cm 2cm 2.5cm 3cm; }
body { font-family: "Times New Roman", Times, serif; font-size: 13pt; line-height: 1.45; color: #000; }
.watermark { text-align: center; font-size: 11pt; font-weight: bold; color: #b45309; border: 1px solid #f59e0b; background: #fffbeb; padding: 6px 10px; margin-bottom: 18px; }
.center { text-align: center; }
.right { text-align: right; }
.bold { font-weight: bold; }
.underline { text-decoration: underline; }
.nation { font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
.motto { font-style: italic; }
.title-main { font-weight: bold; text-transform: uppercase; font-size: 16pt; margin: 18px 0 6px; }
.title-sub { font-weight: bold; text-transform: uppercase; font-size: 13pt; margin: 0 0 14px; }
.meta { margin: 12px 0 18px; }
.party { margin: 10px 0; }
.party-title { font-weight: bold; text-transform: uppercase; margin: 14px 0 6px; }
.line { margin: 3px 0; }
table.info { width: 100%; border-collapse: collapse; margin: 14px 0 18px; }
table.info td { border: 1px solid #333; padding: 7px 10px; vertical-align: top; }
table.info td.label { width: 34%; font-weight: bold; background: #f9f9f9; }
.section-title { font-weight: bold; text-transform: uppercase; text-align: center; margin: 20px 0 10px; }
.clause { margin: 4px 0; text-align: justify; }
.signatures { width: 100%; margin-top: 36px; border-collapse: collapse; }
.signatures td { width: 50%; vertical-align: top; text-align: center; padding: 8px; }
.sign-space { height: 90px; }
.footer-note { font-size: 11pt; color: #555; margin-top: 24px; font-style: italic; text-align: center; }
</style>
</head>
<body>
<div class="watermark">BẢN XEM TRƯỚC — CHƯA KÝ SỐ, CHƯA CÓ HIỆU LỰC PHÁP LÝ</div>

<p class="center nation">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</p>
<p class="center motto">Độc lập – Tự do – Hạnh phúc</p>
<p class="center underline">——————</p>

<p class="center title-main">${escapeHtml(title)}</p>
<p class="center">Số: <span class="bold">${escapeHtml(code)}</span></p>

<p class="meta">Hôm nay, ngày <span class="bold">${todayVi()}</span>, tại ${escapeHtml(companyName)}, chúng tôi gồm:</p>

<p class="party-title">Bên A — Người sử dụng lao động</p>
<div class="party">
<p class="line">Tên doanh nghiệp: <span class="bold">${escapeHtml(companyName)}</span></p>
<p class="line">Đại diện: <span class="bold">${escapeHtml(directorName)}</span> &nbsp;&nbsp; Chức vụ: <span class="bold">Giám đốc</span></p>
</div>

<p class="party-title">Bên B — Người lao động</p>
<div class="party">
<p class="line">Họ và tên: <span class="bold">${escapeHtml(empName)}</span></p>
<p class="line">Mã nhân viên: <span class="bold">${escapeHtml(empCode)}</span></p>
<p class="line">Phòng ban: ${escapeHtml(dept)} &nbsp;&nbsp; Chức vụ: <span class="bold">${escapeHtml(position)}</span></p>
</div>

<p>Hai bên thỏa thuận ký kết hợp đồng lao động với các nội dung sau:</p>

<table class="info">
<tr><td class="label">Loại hợp đồng</td><td>${escapeHtml(typeLabels[ct] || ct || '—')}</td></tr>
<tr><td class="label">Thời hạn</td><td>Từ ngày <span class="bold">${formatDateVi(startDate)}</span> đến ngày <span class="bold">${endLabel}</span></td></tr>
<tr><td class="label">Nơi làm việc</td><td>${escapeHtml(workplace)}</td></tr>
<tr><td class="label">Lương cơ bản</td><td><span class="bold">${fmt(base)}</span> VNĐ/tháng</td></tr>
<tr><td class="label">Phụ cấp chức vụ</td><td>${fmt(allowance)} VNĐ/tháng</td></tr>
<tr><td class="label">Phụ cấp khác</td><td>${fmt(bonus)} VNĐ/tháng</td></tr>
<tr><td class="label">Tổng thu nhập</td><td><span class="bold">${fmt(total)}</span> VNĐ/tháng</td></tr>
<tr><td class="label">Hình thức thanh toán</td><td>Tiền mặt và chuyển khoản</td></tr>
</table>

${formatTermsHtml(terms)}

<table class="signatures">
<tr>
<td>
<p class="bold">ĐẠI DIỆN BÊN A</p>
<p><em>(Ký, ghi rõ họ tên, đóng dấu)</em></p>
<div class="sign-space"></div>
<p class="bold">${escapeHtml(directorName)}</p>
</td>
<td>
<p class="bold">NGƯỜI LAO ĐỘNG — BÊN B</p>
<p><em>(Ký, ghi rõ họ tên)</em></p>
<div class="sign-space"></div>
<p class="bold">${escapeHtml(empName)}</p>
</td>
</tr>
</table>

<p class="footer-note">Hợp đồng lập thành 02 bản có giá trị như nhau. Bản xem trước này chỉ phục vụ in/đối chiếu trước khi lưu nháp và gửi ký số trên hệ thống SmartHR.</p>
</body>
</html>`;
    };

    exportBtn?.addEventListener('click', () => {
        const ct = contractTypeSelect?.value;
        if (!ct) { alert('Vui lòng chọn loại hợp đồng.'); return; }
        const empId = document.getElementById('employeeId')?.value || document.querySelector('[name="employee_id"]')?.value;
        if (!empId && !document.getElementById('employeeName')?.value?.trim()) {
            alert('Vui lòng nhập mã nhân viên và bấm Tìm.');
            return;
        }
        const html = buildContractDocumentHtml();
        const code = (document.querySelector('[name="contract_code"]')?.value || 'hop_dong').replace(/\s+/g, '_');
        const blob = new Blob(['\ufeff', html], { type: 'application/msword;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = code + '_xem_truoc.doc';
        document.body.appendChild(link); link.click(); document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    });

    // Submit thường (không AJAX) — Laravel redirect + flash error rõ ràng hơn
    const form = document.getElementById('contractForm');
    form?.addEventListener('submit', function (e) {
        if (employeeCodeInput && !employeeCodeInput.readOnly) {
            const empId = (employeeIdInput?.value || '').trim();
            if (!empId) {
                e.preventDefault();
                lookupEmployeeByCode();
                alert('Vui lòng nhập đúng mã nhân viên và bấm Tìm trước khi lưu.');
                const btns = form.querySelectorAll('button[type="submit"]');
                btns.forEach(b => b.disabled = false);
                return;
            }
        }
        const btns = form.querySelectorAll('button[type="submit"]');
        btns.forEach(b => b.disabled = true);
    });

    // Init
    updateTotals();
    if (contractTypeSelect?.value) {
        if (qiContractType) qiContractType.textContent = typeLabels[contractTypeSelect.value] || contractTypeSelect.value.replace(/_/g,' ');
        if (!(contractContentField?.value || '').trim()) {
            populateTemplateContent(contractTypeSelect.value);
        }
    }
})();
</script>
@endpush
