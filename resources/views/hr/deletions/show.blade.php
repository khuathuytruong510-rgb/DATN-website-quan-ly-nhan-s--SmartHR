@extends('layouts.app')

@section('title', 'Chi tiết yêu cầu')

@section('content')
@php $req = $deletionRequest; @endphp
<div class="page-head">
    <div>
        <h1>{{ $req->typeLabel() }}: {{ $req->subject_label }}</h1>
    </div>
    <a class="btn link" href="{{ route('deletion_requests.index') }}">Danh sách</a>
</div>

@if(session('success'))
    <div class="alert" style="background:#e6f4ea;border-left:4px solid #137333;padding:0.75rem 1rem;margin-bottom:1rem;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert" style="background:#ffebee;border-left:4px solid #f44336;padding:0.75rem 1rem;margin-bottom:1rem;">{{ session('error') }}</div>
@endif

<div class="card">
    <div class="emp-dl">
        <div><label>Người gửi</label><div>{{ optional($req->requester)->name }} ({{ optional($req->requester)->email }})</div></div>
        <div><label>Lý do</label><div>{{ $req->reason ?: '—' }}</div></div>
        <div>
            <label>Tài liệu</label>
            <div>
                @if($req->document_path)
                    <a href="{{ route('deletion_requests.document', $req) }}">{{ $req->document_name ?: 'Tải file' }}</a>
                @else
                    —
                @endif
            </div>
        </div>
        @if($req->reviewed_at)
            <div><label>Người duyệt</label><div>{{ optional($req->reviewer)->name }} · {{ $req->reviewed_at->format('d/m/Y H:i') }}</div></div>
        @endif
        @if($req->rejection_reason)
            <div><label>Lý do từ chối</label><div>{{ $req->rejection_reason }}</div></div>
        @endif
        @if($req->status === 'approved' && $req->isEmployee() && $req->account_email)
            <div>
                <label>Tài khoản</label>
                <div>
                    {{ $req->account_email }} — đã khóa đăng nhập
                    @if($req->account_cleared_at)
                        lúc {{ $req->account_cleared_at->format('d/m/Y H:i') }}
                    @endif
                </div>
            </div>
        @endif
    </div>

    @if($canReview)
        <div class="actions" style="margin-top:16px;">
            <form method="POST" action="{{ route('deletion_requests.approve', $req) }}">
                @csrf
                <button class="btn primary" type="submit" onclick="return confirm('{{ $req->isTransfer() ? 'Duyệt điều chuyển? Hồ sơ nhân viên sẽ đổi sang phòng ban đích ngay sau khi duyệt.' : ($req->isEmployee() ? 'Duyệt nghỉ việc? Hồ sơ được giữ lại; hợp đồng chấm dứt; tài khoản khóa đăng nhập.' : 'Duyệt và xóa phòng ban? Dữ liệu được lưu vào lịch sử.') }}')">{{ $req->isTransfer() ? 'Duyệt điều chuyển' : ($req->isEmployee() ? 'Duyệt nghỉ việc' : 'Duyệt và xóa') }}</button>
            </form>
            <form method="POST" action="{{ route('deletion_requests.reject', $req) }}" style="display:flex;gap:8px;align-items:center;">
                @csrf
                <input type="text" name="rejection_reason" placeholder="Lý do từ chối" required style="min-width:240px;">
                <button class="btn danger" type="submit">Từ chối</button>
            </form>
        </div>
    @endif
</div>

<div class="card" style="margin-top:16px;">
    <h3 class="section-title" style="margin-top:0;">Dữ liệu lưu trữ</h3>
    @php
        $snapshot = $req->snapshot ?? [];
        $settlement = $snapshot['settlement'] ?? [];
        $employee = $snapshot['employee'] ?? null;
        $transferPeople = $snapshot['employees'] ?? [];
        $savedContracts = $snapshot['contracts'] ?? data_get($snapshot, 'related.contracts', []);
        $contractStatusLabel = function (?string $status): string {
            return match ($status) {
                'waiting_employee_signature', 'waiting_employee' => 'Chờ NV ký',
                'waiting_director_signature', 'waiting_director' => 'Chờ GĐ ký',
                'active' => 'Có hiệu lực',
                'expired', 'expiring' => $status === 'expiring' ? 'Sắp hết hạn' : 'Hết hạn',
                'rejected' => 'Từ chối',
                'cancelled' => 'Đã hủy',
                'terminated' => 'Đã chấm dứt',
                'draft' => 'Nháp',
                default => $status ? ucfirst(str_replace('_', ' ', $status)) : '—',
            };
        };
        $contractTypeLabel = function (?string $type): string {
            return match ($type) {
                'internship' => 'Thực tập',
                'probation' => 'Thử việc',
                'fixed_term' => 'Xác định thời hạn',
                'indefinite' => 'Không xác định thời hạn',
                'official' => 'Chính thức',
                'seasonal' => 'Thời vụ',
                default => $type ? ucfirst(str_replace('_', ' ', $type)) : '—',
            };
        };
        $fmtDate = function ($value): string {
            if (! $value) {
                return '—';
            }
            try {
                return \Carbon\Carbon::parse($value)->format('d/m/Y');
            } catch (\Throwable) {
                return (string) $value;
            }
        };
    @endphp
    @if($req->isTransfer())
        @php $history = $req->transferHistory(); @endphp
        @if($req->isPending())
            <div class="callout warn" style="margin-bottom:12px;">
                <p class="callout-title">Chờ Giám đốc duyệt</p>
                <p style="margin:0;">Hồ sơ nhân viên vẫn thuộc {{ data_get($snapshot, 'from.name') }} cho đến khi được duyệt.</p>
            </div>
        @elseif($req->status === 'rejected')
            <div class="callout warn" style="margin-bottom:12px;">
                <p class="callout-title">Từ chối</p>
                <p style="margin:0;">Hồ sơ giữ nguyên phòng ban {{ data_get($snapshot, 'from.name') }}.</p>
            </div>
        @endif
        @if($history)
            <div class="emp-dl" style="margin-bottom:16px;">
                <div><label>Điều chuyển nhân viên</label><div>{{ collect($history['employees'] ?? [])->pluck('name')->filter()->implode(', ') ?: $req->subject_label }}</div></div>
                <div><label>Từ</label><div>{{ data_get($history, 'from.name') ?: data_get($snapshot, 'from.name') }}</div></div>
                <div><label>Đến</label><div>{{ data_get($history, 'to.name') ?: data_get($snapshot, 'to.name') }}</div></div>
                <div><label>Người duyệt</label><div>{{ $history['approved_by'] ?? optional($req->reviewer)->name }}</div></div>
                <div><label>Ngày duyệt</label><div>{{ ! empty($history['approved_at']) ? \Carbon\Carbon::parse($history['approved_at'])->format('d/m/Y') : optional($req->reviewed_at)->format('d/m/Y') }}</div></div>
                <div><label>Lý do</label><div>{{ $history['reason'] ?? $req->reason ?: '—' }}</div></div>
            </div>
        @endif
        <div class="emp-dl">
            <div><label>Phòng ban hiện tại (lúc gửi)</label><div>{{ data_get($snapshot, 'from.name') }}</div></div>
            <div><label>Phòng ban đích</label><div>{{ data_get($snapshot, 'to.name') }}</div></div>
        </div>
        <table style="margin-top:12px;">
            <thead>
                <tr>
                    <th>Mã NV</th>
                    <th>Họ tên</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transferPeople as $person)
                    <tr>
                        <td><code>{{ $person['employee_code'] ?? '—' }}</code></td>
                        <td>{{ $person['name'] ?? '—' }}</td>
                        <td>{{ $person['email'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">Không có danh sách nhân viên.</td></tr>
                @endforelse
            </tbody>
        </table>
        @php $feedbackEntries = $req->feedbackEntries(); @endphp
        @if($feedbackEntries)
            <h3 class="section-title">Phản hồi nhân viên</h3>
            @foreach($feedbackEntries as $employeeId => $entry)
                <div style="padding:12px 0;border-top:1px solid #eef2ff;">
                    <strong>{{ $entry['employee_name'] ?? ('NV #'.$employeeId) }}</strong>
                    — {{ ($entry['agree'] ?? false) ? 'Đã nắm thông tin' : 'Không đồng ý / có ý kiến' }}
                    <div>{{ $entry['message'] ?? '' }}</div>
                    <div class="muted" style="font-size:13px;">{{ $entry['submitted_at'] ?? '' }}</div>
                    @if(! empty($entry['hr_reply']))
                        <p style="margin:8px 0 0;"><strong>HR đã trả lời:</strong> {{ $entry['hr_reply'] }}</p>
                    @elseif(auth()->user()?->canManageHr())
                        <form method="POST" action="{{ route('deletion_requests.reply_feedback', [$req, $employeeId]) }}" style="margin-top:8px;display:flex;gap:8px;align-items:flex-start;">
                            @csrf
                            <textarea name="reply" rows="2" required placeholder="Nội dung giải quyết gửi lại nhân viên" style="flex:1;"></textarea>
                            <button class="btn primary" type="submit">Gửi giải quyết</button>
                        </form>
                    @else
                        <p class="muted">Chờ HR giải quyết.</p>
                    @endif
                </div>
            @endforeach
        @endif
    @elseif($employee)
        <div class="emp-dl">
            <div><label>Mã NV</label><div>{{ $employee['employee_code'] ?? '—' }}</div></div>
            <div><label>Họ tên</label><div>{{ $employee['name'] ?? '—' }}</div></div>
            <div><label>Email</label><div>{{ $employee['email'] ?? '—' }}</div></div>
            <div><label>Chức vụ</label><div>{{ $employee['position'] ?? '—' }}</div></div>
            <div><label>Phòng ban</label><div>{{ data_get($snapshot, 'department.name') ?? '—' }}</div></div>
            <div><label>Tài khoản</label><div>{{ data_get($snapshot, 'account.email') ?? '—' }}</div></div>
            @if(! empty($snapshot['last_working_day']) || ! empty($settlement))
                <div><label>Ngày nghỉ</label><div>{{ $fmtDate($snapshot['last_working_day'] ?? data_get($settlement, 'last_working_day')) }}</div></div>
            @endif
        </div>
        @if($settlement)
            <h3 class="section-title" style="margin-top:20px;">Chốt công / lương cuối</h3>
            <div class="emp-dl">
                <div><label>Ngày công đến ngày nghỉ</label><div>{{ $settlement['attendance_days'] ?? '—' }}</div></div>
                <div><label>Công trong tháng nghỉ</label><div>{{ $settlement['attendance_days_in_final_month'] ?? '—' }}</div></div>
                <div><label>Đơn OT</label><div>{{ $settlement['overtime_requests'] ?? '—' }}</div></div>
                <div><label>Đơn phép</label><div>{{ $settlement['leave_requests'] ?? '—' }}</div></div>
                @if(data_get($settlement, 'final_payroll.note'))
                    <div><label>Lương cuối</label><div>{{ data_get($settlement, 'final_payroll.note') }}</div></div>
                @endif
            </div>
        @endif
        <h3 class="section-title" style="margin-top:28px;padding-top:8px;">{{ $req->status === 'approved' ? 'Hợp đồng đã chấm dứt' : 'Hợp đồng' }}</h3>
        <table>
            <thead>
                <tr>
                    <th>Mã HĐ</th>
                    <th>Loại</th>
                    <th>Trạng thái</th>
                    <th>Hiệu lực</th>
                    <th>Lương CB</th>
                </tr>
            </thead>
            <tbody>
                @forelse($savedContracts as $contract)
                    <tr>
                        <td><code>{{ $contract['contract_code'] ?? '—' }}</code></td>
                        <td>{{ $contractTypeLabel($contract['contract_type'] ?? null) }}</td>
                        <td>{{ $contractStatusLabel($contract['status'] ?? $contract['contract_status'] ?? null) }}</td>
                        <td>{{ $fmtDate($contract['start_date'] ?? null) }} → {{ $fmtDate($contract['end_date'] ?? null) }}</td>
                        <td>{{ number_format((float) ($contract['base_salary'] ?? $contract['salary'] ?? 0), 0, ',', '.') }}₫</td>
                    </tr>
                    @if(! empty($contract['title']) || ! empty($contract['notes']))
                        <tr>
                            <td colspan="5" class="muted" style="font-size:13px;">
                                @if(! empty($contract['title']))
                                    {{ $contract['title'] }}
                                @endif
                                @if(! empty($contract['notes']))
                                    <div>{{ $contract['notes'] }}</div>
                                @endif
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="5">Không có hợp đồng.</td></tr>
                @endforelse
            </tbody>
        </table>
    @elseif(isset($snapshot['department']))
        <div class="emp-dl">
            <div><label>Mã</label><div>{{ $snapshot['department']['code'] ?? '—' }}</div></div>
            <div><label>Tên</label><div>{{ $snapshot['department']['name'] ?? '—' }}</div></div>
            <div><label>Mô tả</label><div>{{ $snapshot['department']['description'] ?? '—' }}</div></div>
        </div>
    @endif
</div>
@endsection
