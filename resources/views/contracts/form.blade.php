@extends('layouts.app')

@section('content')
<div class="contract-page">
<div class="page-head">
    <div>
        @if(isset($renewingFrom))
            <h1>Gia hạn hợp đồng</h1>
            <p class="muted">Tạo hợp đồng mới kế tiếp từ hợp đồng <strong>{{ $renewingFrom->contract_code }}</strong>.</p>
        @elseif($isEdit)
            <h1>Chỉnh sửa hợp đồng</h1>
            <p class="muted">Cập nhật thông tin hợp đồng.</p>
        @else
            <h1>Tạo hợp đồng mới</h1>
            <p class="muted">Vui lòng nhập đầy đủ thông tin để tạo hợp đồng cho nhân viên.</p>
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
            <div class="field">
                <label>Nhân viên <span class="text-danger">*</span></label>
                @if(isset($renewingFrom))
                    <input type="hidden" name="employee_id" value="{{ $contract->employee_id }}">
                    <input type="text" readonly value="{{ optional($contract->employee)->name ?? '' }}" style="background:#f8fafc;color:#475569;">
                @else
                    <select name="employee_id" id="employeeSelect" {{ $isEdit ? 'disabled' : 'required' }}>
                        <option value="">-- Chọn nhân viên --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ old('employee_id', $contract->employee_id) == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                        @endforeach
                    </select>
                    @if($isEdit)
                        <input type="hidden" name="employee_id" value="{{ $contract->employee_id }}">
                    @endif
                    @error('employee_id')<span class="error">{{ $message }}</span>@enderror
                @endif
            </div>
            <div class="field">
                <label>Mã nhân viên</label>
                <input type="text" id="employeeCode" readonly style="background:#f8fafc;color:#64748b;" value="{{ optional($contract->employee)->employee_code ?? '' }}">
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

    {{-- 2. Thông tin hợp đồng --}}
    <div class="card" style="margin-bottom:20px;">
        <div style="padding:6px 0 16px 0;border-bottom:1px solid var(--line);margin-bottom:16px;">
            <strong>2. Thông tin hợp đồng</strong>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="field">
                <label>Mã hợp đồng</label>
                <input type="text" name="contract_code" value="{{ old('contract_code', $contract->contract_code ?? '') }}" placeholder="Tự động tạo nếu bỏ trống">
                @error('contract_code')<span class="error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label>Loại hợp đồng <span class="text-danger">*</span></label>
                <select name="contract_type" id="contractTypeSelect" required>
                    <option value="">-- Chọn loại hợp đồng --</option>
                    <option value="internship"  {{ old('contract_type', $contract->contract_type) == 'internship'  ? 'selected' : '' }}>Thực tập</option>
                    <option value="probation"   {{ old('contract_type', $contract->contract_type) == 'probation'   ? 'selected' : '' }}>Thử việc</option>
                    <option value="fixed_term"  {{ old('contract_type', $contract->contract_type) == 'fixed_term'  ? 'selected' : '' }}>Lao động xác định thời hạn</option>
                    <option value="indefinite"  {{ old('contract_type', $contract->contract_type) == 'indefinite'  ? 'selected' : '' }}>Lao động không xác định thời hạn</option>
                    <option value="official"    {{ old('contract_type', $contract->contract_type) == 'official'    ? 'selected' : '' }}>Lao động chính thức</option>
                    <option value="seasonal"    {{ old('contract_type', $contract->contract_type) == 'seasonal'    ? 'selected' : '' }}>Hợp đồng thời vụ</option>
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
                <select name="status" id="statusSelect">
                    <option value="waiting_employee_signature" {{ old('status', $contract->status) == 'waiting_employee_signature' ? 'selected' : '' }}>Chờ nhân viên ký</option>
                    <option value="waiting_director_signature" {{ old('status', $contract->status) == 'waiting_director_signature' ? 'selected' : '' }}>Chờ giám đốc ký</option>
                    <option value="active"    {{ old('status', $contract->status) == 'active'    ? 'selected' : '' }}>Có hiệu lực</option>
                    <option value="expiring"  {{ old('status', $contract->status) == 'expiring'  ? 'selected' : '' }}>Sắp hết hạn</option>
                    <option value="expired"   {{ old('status', $contract->status) == 'expired'   ? 'selected' : '' }}>Hết hạn</option>
                    <option value="cancelled" {{ old('status', $contract->status) == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                </select>
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
                <select name="payment_method">
                    <option value="">-- Chọn hình thức --</option>
                    <option value="bank_transfer" {{ old('payment_method', $contract->payment_method) == 'bank_transfer' ? 'selected' : '' }}>Chuyển khoản</option>
                    <option value="cash"          {{ old('payment_method', $contract->payment_method) == 'cash'          ? 'selected' : '' }}>Tiền mặt</option>
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
                    <option value="morning"         {{ old('working_schedule', $contract->working_schedule) == 'morning'         ? 'selected' : '' }}>Sáng</option>
                    <option value="evening"         {{ old('working_schedule', $contract->working_schedule) == 'evening'         ? 'selected' : '' }}>Tối</option>
                    <option value="morning_evening" {{ old('working_schedule', $contract->working_schedule) == 'morning_evening' ? 'selected' : '' }}>Sáng và tối</option>
                </select>
            </div>
            <div class="field">
                <label>Phúc lợi</label>
                <textarea name="benefits" rows="3">{{ old('benefits', $contract->benefits) }}</textarea>
            </div>
            <div class="field">
                <label>Tổng thu nhập</label>
                <input type="text" id="totalIncomeDisplay" readonly style="font-weight:700;background:#f0fdf4;color:#16a34a;" value="">
            </div>
        </div>
    </div>

    {{-- 4. Điều khoản cố định --}}
    <div class="card" style="margin-bottom:20px;">
        <div style="padding:6px 0 16px 0;border-bottom:1px solid var(--line);margin-bottom:16px;">
            <strong>4. Điều khoản cố định</strong>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;">
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

    {{-- 5. Điều khoản hợp đồng --}}
    <div class="card" style="margin-bottom:20px;">
        <div style="padding:6px 0 16px 0;border-bottom:1px solid var(--line);margin-bottom:16px;">
            <strong>5. Điều khoản hợp đồng</strong>
        </div>
        <div id="templateAlert" style="display:none;padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:14px;background:#fffbeb;color:#92400e;border:1px solid #fde68a;">
            <span id="templateAlertText"></span>
        </div>
        <div class="field">
            <label>Nội dung điều khoản</label>
            <p class="muted" style="margin:0 0 8px;">Điều khoản lấy sẵn theo loại hợp đồng (mẫu hệ thống). Không nhập hoặc chỉnh sửa tại đây.</p>
            <div id="templateLoader" style="display:none;margin-bottom:8px;">
                <span style="display:inline-block;width:14px;height:14px;border:2px solid #e2e8f0;border-top-color:#2563eb;border-radius:50%;animation:spin .6s linear infinite;margin-right:6px;vertical-align:middle;"></span>
                <span class="muted">Đang tải mẫu điều khoản...</span>
            </div>
            @php $officialTerms = old('contract_content', $contract->contract_content ?? $contract->terms ?? ''); @endphp
            <pre id="contractContentPreview" style="white-space:pre-wrap;background:#f8fafc;border:1px solid var(--line);border-radius:8px;padding:14px 16px;max-height:420px;overflow:auto;font-family:inherit;line-height:1.7;margin:0;">{{ $officialTerms !== '' ? $officialTerms : 'Chọn loại hợp đồng để hiển thị điều khoản mẫu.' }}</pre>
            <input type="hidden" name="contract_content" id="contractContentField" value="{{ $officialTerms }}">
        </div>
    </div>

    {{-- 6. File hợp đồng --}}
    <div class="card" style="margin-bottom:20px;">
        <div style="padding:6px 0 16px 0;border-bottom:1px solid var(--line);margin-bottom:16px;">
            <strong>6. File hợp đồng</strong>
        </div>
        <div class="field">
            <input type="file" name="document" accept=".pdf,.doc,.docx">
            <span class="muted" style="font-size:13px;">Có thể để trống để lưu online và xuất file sau.</span>
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

    {{-- 7. Ghi chú --}}
    <div class="card" style="margin-bottom:20px;">
        <div style="padding:6px 0 16px 0;border-bottom:1px solid var(--line);margin-bottom:16px;">
            <strong>7. Ghi chú</strong>
        </div>
        <div class="field">
            <textarea name="notes" rows="4">{{ old('notes', $contract->notes) }}</textarea>
        </div>
    </div>

    <div class="actions" style="margin-top:0;margin-bottom:0;">
        <button class="btn primary" type="submit">{{ isset($renewingFrom) ? '🔄 Tạo hợp đồng gia hạn' : 'Lưu' }}</button>
        @if(!isset($renewingFrom))
            <button class="btn" type="button" id="exportContractButton">Xuất file hợp đồng</button>
            <button class="btn" type="submit" name="send_email" value="1">Lưu &amp; Gửi Email</button>
        @endif
        <a class="btn" href="{{ route('contracts.index') }}">Hủy</a>
    </div>
    <p class="muted" style="margin:10px 0 0;font-size:13px;">Sau khi lưu, hợp đồng ở trạng thái chờ nhân viên ký. Nhân viên đăng nhập và ký, rồi Giám đốc ký thì hợp đồng mới có hiệu lực. HR không ký thay nhân viên.</p>

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
            <div class="muted" style="font-size:11px;">Trạng thái</div>
            <div style="margin-top:4px;">
                @php
                    $b = match($contract->status ?? '') {
                        'waiting_employee_signature','waiting_director_signature' => 'warning',
                        'active' => 'success', 'expiring' => 'info', 'expired' => 'danger',
                        'cancelled' => 'secondary', default => 'secondary'
                    };
                    $lbl = match($contract->status ?? '') {
                        'waiting_employee_signature' => 'Chờ NV ký',
                        'waiting_director_signature' => 'Chờ GĐ ký',
                        'active' => 'Có hiệu lực', 'expiring' => 'Sắp hết hạn',
                        'expired' => 'Hết hạn', 'cancelled' => 'Đã hủy', default => 'Chờ xử lý'
                    };
                @endphp
                <span id="qiStatus" class="badge {{ $b }}" style="font-size:10px;">{{ $lbl }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Người ký --}}
<div class="card" style="margin-top:16px;">
    <div style="padding:6px 0 14px 0;border-bottom:1px solid var(--line);margin-bottom:14px;">
        <strong>Người ký kết</strong>
    </div>
    <div class="field" style="margin-bottom:0;">
        <label>Người ký</label>
        <select name="signer_id">
            <option value="">-- Chọn người ký --</option>
            @foreach($signers as $signer)
                <option value="{{ $signer->id }}" {{ old('signer_id', $contract->signer_id) == $signer->id ? 'selected' : '' }}>{{ $signer->name }}</option>
            @endforeach
        </select>
    </div>
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
    const employeeSelect      = document.getElementById('employeeSelect');
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
    const qiStatus            = $('qiStatus');
    const statusSelect        = $('statusSelect');
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

    const typeLabels  = {internship:'Thực tập',probation:'Thử việc',fixed_term:'Xác định TH',indefinite:'Không XĐ TH',official:'LĐ chính thức',seasonal:'Thời vụ'};
    const typeTitles  = {internship:'Hợp đồng thực tập',probation:'Hợp đồng thử việc',fixed_term:'Hợp đồng LĐ xác định thời hạn',indefinite:'Hợp đồng LĐ không xác định thời hạn',official:'Hợp đồng LĐ chính thức',seasonal:'Hợp đồng thời vụ'};
    const statusMap   = s => ({'waiting_employee_signature':['warning','Chờ NV ký'],'waiting_director_signature':['warning','Chờ GĐ ký'],'active':['success','Có hiệu lực'],'expiring':['info','Sắp hết hạn'],'expired':['danger','Hết hạn'],'cancelled':['secondary','Đã hủy']}[s]||['secondary','Chờ xử lý']);

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

    function updateStatusBadge(s) {
        if (!qiStatus) return;
        const [cls, label] = statusMap(s);
        qiStatus.className = `badge ${cls}`;
        qiStatus.textContent = label;
    }

    // Attach formatted-number editing to salary fields
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

    // Employee select
    if (employeeSelect) {
        const populateEmployee = (id) => {
            if (!id) { updateTotals(); return; }
            fetch(`/employees/${id}`, { headers: { 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' } })
                .then(r => r.json())
                .then(data => {
                    if (document.getElementById('employeeCode')) document.getElementById('employeeCode').value = data.employee_code || '';
                    if (document.getElementById('employeeName')) document.getElementById('employeeName').value = data.name || '';
                    if (document.getElementById('employeeDepartment')) document.getElementById('employeeDepartment').value = data.department?.name || '';
                    if (document.getElementById('employeePosition')) document.getElementById('employeePosition').value = data.position || '';
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
                }).catch(() => updateTotals());
        };
        employeeSelect.addEventListener('change', function () { populateEmployee(this.value); });
        if (employeeSelect.value) populateEmployee(employeeSelect.value);
    }

    // No end date checkbox
    noEndDate?.addEventListener('change', function () {
        if (endDateInput) { endDateInput.disabled = this.checked; if (this.checked) endDateInput.value = ''; }
    });
    if (noEndDate?.checked && endDateInput) endDateInput.disabled = true;

    // Status badge
    statusSelect?.addEventListener('change', function () { updateStatusBadge(this.value); });

    // Contract type → title + template content
    const populateTemplateContent = (ct) => {
        if (!ct || !contractContentField) return;

        const applyTemplate = () => {
            previousContractType = ct;
            if (templateLoader) templateLoader.style.display = 'block';
            showAlert(templateAlert, templateAlertText, '');
            fetch(`/contract-templates/content?contract_type=${encodeURIComponent(ct)}`, { headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'} })
                .then(r => r.json())
                .then(data => {
                    if (data.content?.trim()) {
                        contractContentField.value = data.content;
                        autoFilledContent = data.content;
                        userModified = false;
                        if (contentModifiedHint) contentModifiedHint.style.display = 'none';
                    } else {
                        showAlert(templateAlert, templateAlertText, `Chưa cấu hình mẫu hợp đồng cho loại "${typeLabels[ct]||ct}".`);
                    }
                })
                .catch(() => showAlert(templateAlert, templateAlertText, 'Không thể tải mẫu hợp đồng.'))
                .finally(() => { if (templateLoader) templateLoader.style.display = 'none'; });
        };

        if (userModified && contractContentField.value.trim() !== (autoFilledContent || '').trim()) {
            if (typeof window.SmartHrConfirm === 'function') {
                SmartHrConfirm('Bạn đã chỉnh sửa điều khoản. Đổi loại hợp đồng sẽ ghi đè. Tiếp tục?', applyTemplate);
            } else {
                applyTemplate();
            }
            if (contractTypeSelect) contractTypeSelect.value = previousContractType;
            return;
        }
        applyTemplate();
    };

    contractTypeSelect?.addEventListener('change', function () {
        const ct = this.value;
        if (qiContractType) qiContractType.textContent = typeLabels[ct] || (ct ? ct.replace(/_/g,' ') : '—');
        if (contractTitleInput) contractTitleInput.value = typeTitles[ct] || '';
        populateTemplateContent(ct);
    });

    // Export
    exportBtn?.addEventListener('click', () => {
        const content = contractContentField?.value.trim();
        if (!content) { alert('Không có nội dung.'); return; }
        const name = document.querySelector('[name="contract_code"]')?.value || 'hop_dong';
        const blob = new Blob([content], { type:'application/msword;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = name.replace(/\s+/g,'_') + '.doc';
        document.body.appendChild(link); link.click(); document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    });

    // Form submit (AJAX)
    const form = document.getElementById('contractForm');
    form?.addEventListener('submit', function (e) {
        e.preventDefault();
        const btns = form.querySelectorAll('button[type="submit"]');
        btns.forEach(b => b.disabled = true);
        fetch(form.action, { method: form.method || 'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: new FormData(form) })
            .then(async res => {
                const data = await res.json().catch(() => null);
                if (res.ok && data?.success) {
                    window.location.href = data.redirect || '{{ route("contracts.index") }}';
                } else {
                    const msg = data?.errors ? Object.values(data.errors).flat().join('\n') : (data?.message || 'Lỗi lưu hợp đồng.');
                    alert(msg);
                }
            })
            .catch(() => alert('Lỗi mạng.'))
            .finally(() => btns.forEach(b => b.disabled = false));
    });

    // Init
    updateTotals();
    if (contractTypeSelect?.value) {
        if (qiContractType) qiContractType.textContent = typeLabels[contractTypeSelect.value] || contractTypeSelect.value.replace(/_/g,' ');
        if (!(contractContentField?.value || '').trim()) {
            populateTemplateContent(contractTypeSelect.value);
        }
    }
    if (statusSelect) updateStatusBadge(statusSelect.value || '{{ $contract->status ?? "" }}');
})();
</script>
@endpush
