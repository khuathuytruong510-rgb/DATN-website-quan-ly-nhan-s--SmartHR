@extends('layouts.app')

@section('content')
<style>
    .ot-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px; margin-bottom:20px; }
    .ot-actions { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
    .ot-grid { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
    @media (max-width: 900px) { .ot-grid { grid-template-columns: 1fr; } }
</style>

<div class="page-head">
    <div>
        <h1>Tăng ca</h1>
        <p class="muted">HR duyệt đăng ký hoặc chủ động chỉ định. Giờ tính lương = thời gian thực tế trong khung đã duyệt, sau khi HR xác nhận — không lấy từ số giờ nhân viên nhập.</p>
    </div>
</div>

@if(session('error'))
    <div class="alert" style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:16px;">{{ session('error') }}</div>
@endif
@if(session('success'))
    <div class="alert" style="background:#d1fae5;color:#065f46;padding:12px;border-radius:8px;margin-bottom:16px;">{{ session('success') }}</div>
@endif

@php $currentUser = auth()->user(); @endphp

@if($currentUser?->is_hr)
<div class="ot-card">
    <h2>Chỉ định tăng ca</h2>
    <p class="muted">Chọn nhân viên, ngày (từ hôm nay trở đi), khung giờ và lý do. Bản ghi được duyệt ngay — nhân viên không cần đăng ký lại.</p>
    <form method="POST" action="{{ route('overtime_requests.assign') }}">
        @csrf
        <div class="ot-grid">
            <div class="field">
                <label>Nhân viên</label>
                <select name="employee_id" required>
                    <option value="">-- Chọn --</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
                @error('employee_id')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label>Ngày</label>
                <input type="date" name="date" value="{{ old('date') }}" min="{{ now()->toDateString() }}" required>
                @error('date')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label>Từ giờ</label>
                <input type="time" name="start_time" value="{{ old('start_time', '17:30') }}" required>
                @error('start_time')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label>Đến giờ</label>
                <input type="time" name="end_time" value="{{ old('end_time', '20:00') }}" required>
                @error('end_time')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="field">
            <label>Lý do / công việc</label>
            <textarea name="reason" required>{{ old('reason') }}</textarea>
            @error('reason')<div class="error">{{ $message }}</div>@enderror
        </div>
        <button class="btn primary" type="submit">Xác nhận chỉ định</button>
    </form>
</div>
@endif

<div class="ot-card">
    <h2>Chờ duyệt</h2>
    @if($pendingRequests->isEmpty())
        <div class="empty">Không có đăng ký đang chờ.</div>
    @else
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nhân viên</th>
                        <th>Ngày</th>
                        <th>Đăng ký</th>
                        <th>Lý do</th>
                        <th>Khung duyệt (có thể thu hẹp)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingRequests as $ot)
                        <tr>
                            <td>{{ optional($ot->employee)->name }}</td>
                            <td>{{ optional($ot->date)->format('d/m/Y') }}</td>
                            <td>{{ $ot->requestedWindowLabel() }}</td>
                            <td>{{ $ot->reason ?: '—' }}</td>
                            <td>
                                @if(\App\Support\RequestApprover::canReview($currentUser, $ot->employee))
                                    <form method="POST" action="{{ route('overtime_requests.approve', $ot) }}" class="ot-actions">
                                        @csrf
                                        <input type="time" name="approved_start" value="{{ $ot->clock($ot->requestedStartTime()) }}">
                                        <span>–</span>
                                        <input type="time" name="approved_end" value="{{ $ot->clock($ot->requestedEndTime()) }}">
                                        <button class="btn primary" type="submit">Duyệt</button>
                                    </form>
                                    <form method="POST" action="{{ route('overtime_requests.reject', $ot) }}" style="margin-top:8px;" onsubmit="return submitRejectReason(this)">
                                        @csrf
                                        <input type="hidden" name="rejection_reason" value="">
                                        <button class="btn" type="submit">Từ chối</button>
                                    </form>
                                @elseif(\App\Support\RequestApprover::needsDirector($ot->employee))
                                    <span class="muted">Chờ Giám đốc duyệt</span>
                                @endif
                            </td>
                            <td></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="ot-card">
    <h2>Chờ xác nhận giờ thực tế</h2>
    <p class="muted">Hệ thống đã đối chiếu checkout với khung OT đã duyệt. Xác nhận xong mới đưa vào lương.</p>
    @if($completedRequests->isEmpty())
        <div class="empty">Không có bản ghi chờ xác nhận.</div>
    @else
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nhân viên</th>
                        <th>Ngày</th>
                        <th>Được duyệt</th>
                        <th>Thực tế</th>
                        <th>Checkout</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($completedRequests as $ot)
                        <tr>
                            <td>{{ optional($ot->employee)->name }}</td>
                            <td>{{ optional($ot->date)->format('d/m/Y') }}</td>
                            <td>{{ $ot->approvedWindowLabel() }}</td>
                            <td>{{ $ot->actualWindowLabel() }}</td>
                            <td>{{ optional($ot->attendance?->check_out)->format('H:i') ?? '—' }}</td>
                            <td>
                                @if(\App\Support\RequestApprover::canReview($currentUser, $ot->employee))
                                    <form method="POST" action="{{ route('overtime_requests.verify', $ot) }}">
                                        @csrf
                                        <button class="btn primary" type="submit">Xác nhận đưa vào lương</button>
                                    </form>
                                @else
                                    <span class="muted">Chờ cấp có thẩm quyền xác nhận</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="ot-card">
    <h2>Gần đây</h2>
    @if($recentRequests->isEmpty())
        <div class="empty">Chưa có bản ghi khác.</div>
    @else
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nhân viên</th>
                        <th>Nguồn</th>
                        <th>Ngày</th>
                        <th>Đăng ký</th>
                        <th>Duyệt</th>
                        <th>Thực tế</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentRequests as $ot)
                        <tr>
                            <td>{{ optional($ot->employee)->name }}</td>
                            <td>{{ $ot->sourceLabel() }}</td>
                            <td>{{ optional($ot->date)->format('d/m/Y') }}</td>
                            <td>{{ $ot->requestedWindowLabel() }}</td>
                            <td>{{ $ot->approvedWindowLabel() }}</td>
                            <td>{{ $ot->actualWindowLabel() }}</td>
                            <td>{{ $ot->statusLabel() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<script>
function submitRejectReason(form) {
    const reason = window.prompt('Lý do từ chối');
    if (reason === null || String(reason).trim() === '') {
        return false;
    }
    form.querySelector('[name="rejection_reason"]').value = String(reason).trim();
    return true;
}
</script>
@endsection
