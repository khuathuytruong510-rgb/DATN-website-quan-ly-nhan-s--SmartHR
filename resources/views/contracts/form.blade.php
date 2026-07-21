@extends('layouts.app')

@section('content')
<div class="contract-page">
<div class="page-head">
    <div>
        <h1>{{ $isEdit ? 'Chỉnh sửa hợp đồng' : 'Tạo hợp đồng mới' }}</h1>
        <p class="muted">Vui lòng nhập đầy đủ thông tin để tạo hợp đồng cho nhân viên.</p>
    </div>
    <a class="btn link" href="{{ route('contracts.index') }}">Quay lại</a>
</div>

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div id="formMessages"></div>

<form method="POST" action="{{ $isEdit ? route('contracts.update', $contract) : route('contracts.store') }}" enctype="multipart/form-data">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div style="display: flex; flex-direction: column; gap: 16px; width: 100%;">
        <div style="flex: 1; min-width: 0; overflow-y: auto; padding-right: 6px; max-height: calc(100vh - 280px);">
        <div>
            <div class="card" style="margin-bottom: 20px;">
                <div style="padding: 6px 0 16px 0; border-bottom: 1px solid var(--line); margin-bottom: 16px;">
                    <strong>1. Thông tin nhân viên</strong>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="field">
                        <label>Nhân viên <span class="text-danger">*</span></label>
                        <select name="employee_id" id="employeeSelect" required>
                            <option value="">-- Chọn nhân viên --</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ old('employee_id', $contract->employee_id) == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                            @endforeach
                        </select>
                        @error('employee_id')<span class="error">{{ $message }}</span>@enderror
                    </div>
                    <div class="field">
                        <label>Mã nhân viên</label>
                        <input type="text" id="employeeCode" readonly style="background: #f8fafc; color: #64748b;" value="{{ optional($contract->employee)->employee_code ?? '' }}">
                    </div>
                    <div class="field">
                        <label>Họ tên</label>
                        <input type="text" id="employeeName" readonly value="{{ optional($contract->employee)->name ?? '' }}">
                    </div>
                    <div class="field">
                        <label>Phòng ban</label>
                        <input type="text" id="employeeDepartment" readonly value="{{ optional(optional($contract->employee)->department)->name ?? '' }}">
                    </div>
                    <div class="field">
                        <label>Chức vụ</label>
                        <input type="text" id="employeePosition" readonly value="{{ optional($contract->employee)->position ?? '' }}">
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom: 20px;">
                <div style="padding: 6px 0 16px 0; border-bottom: 1px solid var(--line); margin-bottom: 16px;">
                    <strong>2. Thông tin hợp đồng</strong>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="field">
                        <label>Mã hợp đồng</label>
                        <input type="text" name="contract_code" value="{{ old('contract_code', $contract->contract_code ?? '') }}" readonly>
                    </div>
                    <div class="field">
                        <label>Loại hợp đồng <span class="text-danger">*</span></label>
                        <select name="contract_type" id="contractTypeSelect" required>
                            <option value="">-- Chọn loại hợp đồng --</option>
                            <option value="internship" {{ old('contract_type', $contract->contract_type) == 'internship' ? 'selected' : '' }}>Thực tập</option>
                            <option value="probation" {{ old('contract_type', $contract->contract_type) == 'probation' ? 'selected' : '' }}>Thử việc</option>
                            <option value="fixed_term" {{ old('contract_type', $contract->contract_type) == 'fixed_term' ? 'selected' : '' }}>Lao động xác định thời hạn</option>
                            <option value="indefinite" {{ old('contract_type', $contract->contract_type) == 'indefinite' ? 'selected' : '' }}>Lao động không xác định thời hạn</option>
                            <option value="official" {{ old('contract_type', $contract->contract_type) == 'official' ? 'selected' : '' }}>Lao động chính thức</option>
                            <option value="seasonal" {{ old('contract_type', $contract->contract_type) == 'seasonal' ? 'selected' : '' }}>Hợp đồng thời vụ</option>
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
                        <label style="font-weight: 400; margin-top: 6px;">
                            <input type="checkbox" id="noEndDate" {{ old('no_end_date', $contract->end_date ? false : true) ? 'checked' : '' }}>
                            Không xác định thời hạn
                        </label>
                    </div>
                    <div class="field">
                        <label>Trạng thái</label>
                        <select name="status">
                            <option value="waiting_employee" {{ old('status', $contract->status) == 'waiting_employee' ? 'selected' : '' }}>Chờ nhân viên ký</option>
                            <option value="waiting_director" {{ old('status', $contract->status) == 'waiting_director' ? 'selected' : '' }}>Chờ giám đốc ký</option>
                            <option value="active" {{ old('status', $contract->status) == 'active' ? 'selected' : '' }}>Có hiệu lực</option>
                            <option value="expiring" {{ old('status', $contract->status) == 'expiring' ? 'selected' : '' }}>Sắp hết hạn</option>
                            <option value="expired" {{ old('status', $contract->status) == 'expired' ? 'selected' : '' }}>Hết hạn</option>
                            <option value="cancelled" {{ old('status', $contract->status) == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                        </select>
                    </div>
                    <input type="hidden" name="title" id="contractTitleInput" value="{{ old('title', $contract->title ?? '') }}">
                </div>
            </div>

            <div class="card" style="margin-bottom: 20px;">
                <div style="padding: 6px 0 16px 0; border-bottom: 1px solid var(--line); margin-bottom: 16px;">
                    <strong>3. Thông tin lương</strong>
                </div>
                <div id="salaryAlert" style="display:none; padding: 10px 14px; border-radius: 8px; margin-bottom: 14px; font-size: 14px; background:#fffbeb; color:#92400e; border:1px solid #fde68a;">
                    <span id="salaryAlertText"></span>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="field">
                        <label>Lương cơ bản <span class="text-danger">*</span></label>
                        <input type="text" id="baseSalaryDisplay" readonly value="{{ number_format(old('base_salary', $contract->salary ?? $contract->base_salary ?? 0), 0, ',', '.') }}">
                        <input type="hidden" name="base_salary" id="baseSalaryInput" value="{{ old('base_salary', $contract->salary ?? $contract->base_salary ?? 0) }}">
                        @error('base_salary')<span class="error">{{ $message }}</span>@enderror
                    </div>
                    <div class="field">
                        <label>Phụ cấp chức vụ</label>
                        <input type="text" id="allowanceDisplay" readonly value="{{ number_format(old('allowance', $contract->allowance ?? 0), 0, ',', '.') }}">
                        <input type="hidden" name="allowance" id="allowanceInput" value="{{ old('allowance', $contract->allowance ?? 0) }}">
                    </div>
                    <div class="field">
                        <label>Phụ cấp khác</label>
                        <input type="text" id="bonusDisplay" value="{{ old('bonus', $contract->bonus ?? 0) > 0 ? number_format(old('bonus', $contract->bonus ?? 0), 0, ',', '.') : '' }}" placeholder="0">
                        <input type="hidden" name="bonus" id="bonusInput" value="{{ old('bonus', $contract->bonus ?? 0) }}">
                    </div>
                    <div class="field">
                        <label>Hình thức thanh toán</label>
                        <select name="payment_method">
                            <option value="">-- Chọn hình thức --</option>
                            <option value="bank_transfer" {{ old('payment_method', $contract->payment_method) == 'bank_transfer' ? 'selected' : '' }}>Chuyển khoản</option>
                            <option value="cash" {{ old('payment_method', $contract->payment_method) == 'cash' ? 'selected' : '' }}>Tiền mặt</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Nơi làm việc</label>
                        <input type="text" name="workplace" value="{{ old('workplace', $contract->workplace) }}">
                    </div>
                    <div class="field">
                        <label>Ca làm việc</label>
                        <select name="working_schedule">
                            <option value="">-- Chọn ca --</option>
                            <option value="morning" {{ old('working_schedule', $contract->working_schedule) == 'morning' ? 'selected' : '' }}>Sáng</option>
                            <option value="evening" {{ old('working_schedule', $contract->working_schedule) == 'evening' ? 'selected' : '' }}>Tối</option>
                            <option value="morning_evening" {{ old('working_schedule', $contract->working_schedule) == 'morning_evening' ? 'selected' : '' }}>Sáng và tối</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Phúc lợi</label>
                        <textarea name="benefits" rows="3">{{ old('benefits', $contract->benefits) }}</textarea>
                    </div>
                    <div class="field">
                        <label>Tổng thu nhập</label>
                        <input type="text" id="totalIncomeDisplay" readonly style="font-weight: 700;" value="{{ number_format((old('base_salary', $contract->salary ?? $contract->base_salary ?? 0) + (old('allowance', $contract->allowance ?? 0) ?? 0) + (old('bonus', $contract->bonus ?? 0) ?? 0)), 0, ',', '.') }}">
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom: 20px;">
                <div style="padding: 6px 0 16px 0; border-bottom: 1px solid var(--line); margin-bottom: 16px;">
                    <strong>4. Điều khoản cố định</strong>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
                    <div class="field">
                        <label>Nghỉ phép không lương (ngày/tháng)</label>
                        <input type="number" name="allowed_unpaid_leave_days_per_month" min="0" max="31" value="{{ old('allowed_unpaid_leave_days_per_month', $contract->allowed_unpaid_leave_days_per_month ?? 1) }}">
                    </div>
                    <div class="field">
                        <label>Điểm danh bù (lần/tháng)</label>
                        <input type="number" name="allowed_makeup_attendance_per_month" min="0" max="31" value="{{ old('allowed_makeup_attendance_per_month', $contract->allowed_makeup_attendance_per_month ?? 3) }}">
                    </div>
                    <div class="field">
                        <label>Nghỉ thai sản (ngày)</label>
                        <input type="number" name="allowed_maternity_leave_days" min="0" max="365" value="{{ old('allowed_maternity_leave_days', $contract->allowed_maternity_leave_days ?? 180) }}">
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom: 20px;">
                <div style="padding: 6px 0 16px 0; border-bottom: 1px solid var(--line); margin-bottom: 16px;">
                    <strong>5. Điều khoản hợp đồng</strong>
                </div>
                <div id="templateAlert" style="display:none; padding: 10px 14px; border-radius: 8px; margin-bottom: 14px; font-size: 14px; background:#fffbeb; color:#92400e; border:1px solid #fde68a;">
                    <span id="templateAlertText"></span>
                </div>
                <div class="field">
                    <label>Nội dung điều khoản</label>
                    <div id="templateLoader" style="display:none; margin-bottom: 8px;">
                        <span style="display:inline-block; width:14px; height:14px; border:2px solid #e2e8f0; border-top-color:#2563eb; border-radius:50%; animation: spin .6s linear infinite; margin-right:6px; vertical-align:middle;"></span>
                        <span class="muted">Đang tải mẫu điều khoản...</span>
                    </div>
                    <textarea name="contract_content" id="contractContentField" rows="12" style="font-family: inherit; line-height: 1.7;" data-original-content="{{ e(old('contract_content', $contract->contract_content ?? $contract->terms)) }}">{{ old('contract_content', $contract->contract_content ?? $contract->terms) }}</textarea>
                    <div id="contentModifiedHint" style="display:none; color:#dc2626; font-size: 13px; margin-top:4px;">
                        ⚠️ Bạn đã chỉnh sửa nội dung điều khoản.
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom: 20px;">
                <div style="padding: 6px 0 16px 0; border-bottom: 1px solid var(--line); margin-bottom: 16px;">
                    <strong>6. File hợp đồng</strong>
                </div>
                <div class="field">
                    <input type="file" name="document" accept=".pdf,.doc,.docx">
                    <span class="muted" style="font-size: 13px;">Có thể để trống để lưu online và xuất file sau.</span>
                </div>
                @error('document')<span class="error">{{ $message }}</span>@enderror
                @if($contract->document_name)
                    <div style="margin-top: 8px;">
                        <span class="muted">File hiện tại:</span> <strong>{{ $contract->document_name }}</strong>
                        @if($contract->document_path)
                            <a class="btn link" href="{{ Storage::url($contract->document_path) }}" target="_blank">Xem</a>
                        @endif
                    </div>
                @endif
            </div>

            <div class="card" style="margin-bottom: 20px;">
                <div style="padding: 6px 0 16px 0; border-bottom: 1px solid var(--line); margin-bottom: 16px;">
                    <strong>7. Ghi chú</strong>
                </div>
                <div class="field">
                    <textarea name="notes" rows="4">{{ old('notes', $contract->notes) }}</textarea>
                </div>
            </div>

        </div>
        <div class="actions" style="flex-shrink: 0; margin-top: 0; margin-bottom: 0;">
            <button class="btn primary" type="submit">Lưu</button>
            <button class="btn primary" type="submit" name="sign_and_save" value="1" style="background:#059669;">Lưu &amp; Ký</button>
            <button class="btn" type="button" id="exportContractButton">Xuất file hợp đồng</button>
            <button class="btn" type="submit" name="send_email" value="1">Lưu &amp; Gửi Email</button>
            <a class="btn" href="{{ route('contracts.index') }}">Hủy</a>
        </div>
    </div>
</form>

<div class="card" style="margin-top: 16px;">
    <div style="padding: 6px 0 14px 0; border-bottom: 1px solid var(--line); margin-bottom: 14px;">
        <strong>Thông tin nhanh</strong>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 14px;">
            <div class="muted" style="font-size: 11px; text-transform: uppercase; letter-spacing: .08em;">Tổng thu nhập</div>
            <div style="font-size: 22px; font-weight: 800; margin-top: 6px;" id="qiHighlightTotalIncome">{{ number_format(($contract->salary ?? $contract->base_salary ?? 0) + ($contract->allowance ?? 0) + ($contract->bonus ?? 0), 0, ',', '.') }} VNĐ</div>
        </div>
        <div>
            <div class="muted" style="font-size: 11px;\">Loại hợp đồng</div>
            <div style="font-weight: 600; margin-top: 4px;" id="qiContractType">
                @php $l = ['internship'=>'Thực tập','probation'=>'Thử việc','fixed_term'=>'Xác định thời hạn','indefinite'=>'Không xác định thời hạn','official'=>'LĐ chính thức','seasonal'=>'Thời vụ']; @endphp
                {{ $l[$contract->contract_type] ?? ($contract->contract_type ?: '—') }}
            </div>
        </div>
        <div>
            <div class="muted" style="font-size: 11px;\">Lương cơ bản</div>
            <div style="font-weight: 600; margin-top: 4px;" id="qiBaseSalary">{{ number_format($contract->salary ?? $contract->base_salary ?? 0, 0, ',', '.') }} VNĐ</div>
        </div>
        <div>
            <div class="muted" style="font-size: 11px;\">Phụ cấp</div>
            <div style="font-weight: 600; margin-top: 4px;" id="qiAllowance">{{ number_format($contract->allowance ?? 0, 0, ',', '.') }} VNĐ</div>
        </div>
        <div>
            <div class="muted" style="font-size: 11px;\">Phụ cấp khác</div>
            <div style="font-weight: 600; margin-top: 4px;" id="qiBonus">{{ number_format($contract->bonus ?? 0, 0, ',', '.') }} VNĐ</div>
        </div>
        <div>
            <div class="muted" style="font-size: 11px;\">Tổng lương</div>
            <div style="font-weight: 600; margin-top: 4px; color:#2563eb;" id="qiTotalIncome">{{ number_format(($contract->salary ?? $contract->base_salary ?? 0) + ($contract->allowance ?? 0) + ($contract->bonus ?? 0), 0, ',', '.') }} VNĐ</div>
        </div>
        <div>
            <div class="muted" style="font-size: 11px;\">Trạng thái</div>
            <div style="margin-top: 4px;">
                @php
                    $b = match($contract->status) { 'waiting_employee_signature','waiting_director_signature','waiting_employee','waiting_director'=>'warning', 'active'=>'success', 'expiring'=>'info', 'expired'=>'danger', default=>'secondary' };
                    $lbl = match($contract->status) { 'waiting_employee_signature'=>'Chờ NV ký','waiting_director_signature'=>'Chờ GĐ ký','waiting_employee'=>'Chờ NV ký','waiting_director'=>'Chờ GĐ ký','active'=>'Có hiệu lực','expiring'=>'Sắp hết hạn','expired'=>'Hết hạn','rejected'=>'Từ chối','cancelled'=>'Đã hủy', default=>'Chờ xử lý' };
                @endphp
                <span id="qiStatus" class="badge {{ $b }}" style="font-size: 10px;">{{ $lbl }}</span>
            </div>
        </div>
    </div>
</div>

<div class="card" style="margin-top: 16px;">
    <div style="padding: 6px 0 14px 0; border-bottom: 1px solid var(--line); margin-bottom: 14px;">
        <strong>Người ký</strong>
    </div>
    <div class="field" style="margin-bottom: 0;">
        <label>Người ký kết</label>
        <select name="signer_id">
            <option value="">-- Chọn người ký --</option>
            @foreach($signers as $signer)
                <option value="{{ $signer->id }}" {{ old('signer_id', $contract->signer_id) == $signer->id ? 'selected' : '' }}>{{ $signer->name }}</option>
            @endforeach
        </select>
    </div>
</div>
</div>

<style>
    @keyframes spin { to { transform: rotate(360deg); } }
    .summary-item { display: flex; flex-direction: column; gap: 4px; padding: 0; border: none; font-size: 13px; }
    .summary-item:last-child { border-bottom: none; }
    .badge { display: inline-flex; border-radius: 999px; padding: 4px 8px; font-size: 11px; font-weight: 700; }
    .badge.warning { background: #fef3c7; color: #92400e; }
    .badge.success { background: #dcfce7; color: #166534; }
    .badge.info { background: #e0f2fe; color: #0369a1; }
    .badge.danger { background: #fee2e2; color: #dc2626; }
    .badge.secondary { background: #f1f5f9; color: #475569; }
    .badge.dark { background: #1e293b; color: #f8fafc; }
</style>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');
    const employeeSelect = document.getElementById('employeeSelect');
    const employeeCode = document.getElementById('employeeCode');
    const employeeName = document.getElementById('employeeName');
    const employeeDepartment = document.getElementById('employeeDepartment');
    const employeePosition = document.getElementById('employeePosition');
    const baseSalaryInput = document.getElementById('baseSalaryInput');
    const baseSalaryDisplay = document.getElementById('baseSalaryDisplay');
    const allowanceInput = document.getElementById('allowanceInput');
    const allowanceDisplay = document.getElementById('allowanceDisplay');
    const bonusInput = document.getElementById('bonusInput');
    const bonusDisplay = document.getElementById('bonusDisplay');
    const totalIncomeDisplay = document.getElementById('totalIncomeDisplay');
    const qiContractType = document.getElementById('qiContractType');
    const qiBaseSalary = document.getElementById('qiBaseSalary');
    const qiAllowance = document.getElementById('qiAllowance');
    const qiBonus = document.getElementById('qiBonus');
    const qiTotalIncome = document.getElementById('qiTotalIncome');
    const qiHighlightTotalIncome = document.getElementById('qiHighlightTotalIncome');
    const qiStatus = document.getElementById('qiStatus');
    const statusSelect = document.querySelector('select[name="status"]');
    const contractTypeSelect = document.getElementById('contractTypeSelect');
    const contractTitleInput = document.getElementById('contractTitleInput');
    const contractContentField = document.getElementById('contractContentField');
    const exportContractButton = document.getElementById('exportContractButton');
    const templateLoader = document.getElementById('templateLoader');
    const templateAlert = document.getElementById('templateAlert');
    const templateAlertText = document.getElementById('templateAlertText');
    const salaryAlert = document.getElementById('salaryAlert');
    const salaryAlertText = document.getElementById('salaryAlertText');
    const contentModifiedHint = document.getElementById('contentModifiedHint');
    const noEndDate = document.getElementById('noEndDate');
    const endDateInput = document.getElementById('endDateInput');

    const typeLabels = { internship:'Thực tập', probation:'Thử việc', fixed_term:'Xác định thời hạn', indefinite:'Không xác định thời hạn', official:'LĐ chính thức', seasonal:'Thời vụ' };
    const typeTitles = { internship:'Hợp đồng thực tập', probation:'Hợp đồng thử việc', fixed_term:'Hợp đồng lao động xác định thời hạn', indefinite:'Hợp đồng lao động không xác định thời hạn', official:'Hợp đồng LĐ chính thức', seasonal:'Hợp đồng thời vụ' };

    let previousContractType = contractTypeSelect?.value || '';
    let autoFilledContent = contractContentField?.dataset.originalContent || '';
    let userModified = false;

    const statusMap = (s) => ({
        'waiting_employee_signature':['warning','Chờ NV ký'],'waiting_director_signature':['warning','Chờ GĐ ký'],
        'waiting_employee':['warning','Chờ NV ký'],'waiting_director':['warning','Chờ GĐ ký'],
        'active':['success','Có hiệu lực'],'expiring':['info','Sắp hết hạn'],'expired':['danger','Hết hạn'],
        'rejected':['dark','Từ chối'],'cancelled':['secondary','Đã hủy'],
    }[s] || ['secondary','Chờ xử lý']);

    const updateStatusBadge = (s) => {
        if (!qiStatus) return;
        const [cls, label] = statusMap(s);
        qiStatus.className = `badge ${cls}`;
        qiStatus.textContent = label;
    };

    const fmt = (n) => (Number(n)||0).toLocaleString('vi-VN', {maximumFractionDigits:0});

    const updateTotals = () => {
        const base = Number(baseSalaryInput?.value) || 0;
        const allowance = Number(allowanceInput?.value) || 0;
        const bonus = Number(bonusInput?.value) || 0;
        const total = base + allowance + bonus;
        if (baseSalaryDisplay) baseSalaryDisplay.value = fmt(base);
        if (allowanceDisplay) allowanceDisplay.value = fmt(allowance);
        if (totalIncomeDisplay) totalIncomeDisplay.value = fmt(total);
        if (qiBaseSalary) qiBaseSalary.textContent = fmt(base) + ' VNĐ';
        if (qiAllowance) qiAllowance.textContent = fmt(allowance) + ' VNĐ';
        if (qiBonus) qiBonus.textContent = fmt(bonus) + ' VNĐ';
        if (qiTotalIncome) qiTotalIncome.textContent = fmt(total) + ' VNĐ';
        if (qiHighlightTotalIncome) qiHighlightTotalIncome.textContent = fmt(total) + ' VNĐ';
    };

    const showAlert = (el, textEl, msg, type = 'info') => {
        if (!el || !textEl) return;
        if (msg) { el.style.display = 'block'; textEl.textContent = msg; }
        else { el.style.display = 'none'; textEl.textContent = ''; }
    };

    const populateEmployeeDetails = function (employeeId) {
        if (!employeeId) {
            if (employeeCode) employeeCode.value = '';
            if (employeeName) employeeName.value = '';
            if (employeeDepartment) employeeDepartment.value = '';
            if (employeePosition) employeePosition.value = '';
            if (baseSalaryInput) { baseSalaryInput.value = 0; allowanceInput.value = 0; }
            updateTotals();
            showAlert(salaryAlert, salaryAlertText, '');
            return;
        }
        fetch(`/employees/${employeeId}`, { headers: { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            if (employeeCode) employeeCode.value = data.employee_code || '';
            if (employeeName) employeeName.value = data.name || '';
            if (employeeDepartment) employeeDepartment.value = data.department?.name || '';
            if (employeePosition) employeePosition.value = data.position || '';
            if (data.position_base_salary !== null || data.position_allowance !== null) {
                baseSalaryInput.value = data.position_base_salary || 0;
                allowanceInput.value = data.position_allowance || 0;
                updateTotals();
                showAlert(salaryAlert, salaryAlertText, '');
            } else if (data.position_id || data.position) {
                const posUrl = data.position_id ? `/api/positions/id/${data.position_id}` : `/api/positions/${encodeURIComponent(data.position)}`;
                fetch(posUrl, { headers: { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' } })
                .then(r => r.json())
                .then(pos => {
                    if (pos.found) {
                        baseSalaryInput.value = pos.base_salary || pos.salary_range_min || 0;
                        allowanceInput.value = pos.allowance || 0;
                        updateTotals();
                        showAlert(salaryAlert, salaryAlertText, '');
                    } else {
                        showAlert(salaryAlert, salaryAlertText, 'Chưa cấu hình mức lương cho chức vụ này.');
                    }
                }).catch(() => updateTotals());
            } else {
                showAlert(salaryAlert, salaryAlertText, 'Không xác định chức vụ để lấy lương.');
                updateTotals();
            }
        }).catch(() => {});
    };

    employeeSelect?.addEventListener('change', function () { populateEmployeeDetails(this.value); });
    if (employeeSelect?.value) populateEmployeeDetails(employeeSelect.value);

    noEndDate?.addEventListener('change', function () {
        if (endDateInput) { endDateInput.disabled = this.checked; if (this.checked) endDateInput.value = ''; }
    });
    if (noEndDate?.checked && endDateInput) endDateInput.disabled = true;

    statusSelect?.addEventListener('change', function () { updateStatusBadge(this.value); });

    if (contractTypeSelect?.value && contractTitleInput && !contractTitleInput.value) {
        contractTitleInput.value = typeTitles[contractTypeSelect.value] || '';
    }

    const populateTemplateContent = function (ct) {
        if (!ct) { showAlert(templateAlert, templateAlertText, ''); return; }
        if (userModified && contractContentField && contractContentField.value.trim() !== (autoFilledContent||'').trim()) {
            if (!confirm('Bạn đã chỉnh sửa điều khoản. Đổi loại hợp đồng sẽ ghi đè. Tiếp tục?')) {
                contractTypeSelect.value = previousContractType;
                return;
            }
        }
        previousContractType = contractTypeSelect?.value || '';
        if (templateLoader) templateLoader.style.display = 'block';
        showAlert(templateAlert, templateAlertText, '');

        fetch(`/contract-templates/content?contract_type=${encodeURIComponent(ct)}`, {
            headers: { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.content && data.content.trim()) {
                contractContentField.value = data.content;
                autoFilledContent = data.content;
                userModified = false;
                if (contentModifiedHint) contentModifiedHint.style.display = 'none';
            } else {
                showAlert(templateAlert, templateAlertText, 'Chưa cấu hình mẫu hợp đồng cho loại "'+(typeLabels[ct]||ct)+'".');
            }
        }).catch(() => showAlert(templateAlert, templateAlertText, 'Không thể tải mẫu hợp đồng.'))
        .finally(() => { if (templateLoader) templateLoader.style.display = 'none'; });
    };

    contractTypeSelect?.addEventListener('change', function () {
        const ct = this.value;
        if (qiContractType) qiContractType.textContent = typeLabels[ct] || (ct ? ct.replace(/_/g,' ') : '—');
        if (contractTitleInput) contractTitleInput.value = typeTitles[ct] || '';
        populateTemplateContent(ct);
    });

    contractContentField?.addEventListener('input', function () {
        userModified = true;
        if (contentModifiedHint) contentModifiedHint.style.display = 'block';
    });

    bonusDisplay?.addEventListener('input', function () {
        const raw = this.value.replace(/\./g,'');
        const num = parseInt(raw) || 0;
        bonusInput.value = num;
        this.value = num > 0 ? fmt(num) : '';
        updateTotals();
    });
    bonusDisplay?.addEventListener('blur', function () {
        this.value = (Number(bonusInput.value)||0) > 0 ? fmt(bonusInput.value) : '';
    });
    bonusDisplay?.addEventListener('focus', function () { this.value = bonusInput.value || ''; });

    exportContractButton?.addEventListener('click', function () {
        const content = contractContentField?.value.trim();
        if (!content) { alert('Không có nội dung.'); return; }
        const name = document.querySelector('[name="contract_code"]')?.value || 'hop_dong';
        const blob = new Blob([content], { type:'application/msword;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = name.replace(/\s+/g,'_')+'.doc';
        document.body.appendChild(link); link.click(); document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    });

    form?.addEventListener('submit', function (e) {
        e.preventDefault();
        const btns = form.querySelectorAll('button[type="submit"]');
        btns.forEach(b => b.disabled = true);
        const raw = bonusDisplay?.value.replace(/\./g,'') || '0';
        bonusInput.value = parseInt(raw) || 0;
        const fd = new FormData(form);
        fetch(form.action, {
            method: form.method || 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd,
        })
        .then(async res => {
            const data = await res.json().catch(() => null);
            if (res.ok && data?.success) {
                if (data.redirect) return window.location.href = data.redirect;
                window.location.href = '{{ route("contracts.index") }}';
            } else {
                alert(data?.errors ? Object.values(data.errors).flat().join('\n') : (data?.message || 'Lỗi lưu hợp đồng.'));
            }
        }).catch(() => alert('Lỗi mạng.'))
        .finally(() => btns.forEach(b => b.disabled = false));
    });

    updateTotals();
    if (statusSelect) updateStatusBadge(statusSelect.value || '{{ $contract->status }}');
    if (contractTypeSelect?.value) {
        autoFilledContent = contractContentField?.dataset.originalContent || '';
    }
});
</script>
@endpush
