@extends('layouts.app')

@section('title', 'Quản lý bảng lương')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('accountant.dashboard') }}">Kế toán</a></li>
<li>Quản lý bảng lương</li>
@endsection

<div class="page-head">
    <div>
        <h1>Bảng lương</h1>
        <p class="muted" style="margin:4px 0 0;">Hệ thống tự tính sau khi HR xác nhận nguồn → Kế toán kiểm tra gửi Giám đốc → GĐ duyệt và thông báo nhân viên.</p>
    </div>
    <div class="actions" style="display:flex;gap:8px;flex-wrap:wrap;">
        <a class="btn" href="{{ route('accountant.payroll.generate') }}">Xem / tính lại kỳ</a>
        @if(($pendingSubmitCount ?? 0) > 0)
        <form method="POST" action="{{ route('accountant.payroll.submit_all') }}"
              data-confirm="Gửi duyệt tất cả {{ $pendingSubmitCount }} phiếu tháng {{ sprintf('%02d/%d', $filterMonth, $filterYear) }} sang Giám đốc?">
            @csrf
            <input type="hidden" name="month" value="{{ $filterMonth }}">
            <input type="hidden" name="year" value="{{ $filterYear }}">
            <button class="btn primary" type="submit">Gửi duyệt tất cả ({{ $pendingSubmitCount }})</button>
        </form>
        @endif
    </div>
</div>

<div class="card">
    <form method="GET" class="row" style="display:flex; gap:12px; align-items:center; margin-bottom:12px; flex-wrap:wrap;">
        <input name="q" placeholder="Tìm theo tên hoặc email" value="{{ request('q') }}">
        <select name="month" aria-label="Tháng">
            <option value="">Tất cả tháng</option>
            @for($month = 1; $month <= 12; $month++)
                <option value="{{ $month }}" @selected((string) request('month', now()->month) === (string) $month)>Tháng {{ $month }}</option>
            @endfor
        </select>
        <select name="year" aria-label="Năm">
            <option value="">Tất cả năm</option>
            @foreach($payrollYears as $year)
                <option value="{{ $year }}" @selected((string) request('year', now()->year) === (string) $year)>{{ $year }}</option>
            @endforeach
        </select>
        <select name="status">
            <option value="">Tất cả trạng thái</option>
            <option value="calculated" {{ request('status')=='calculated' ? 'selected' : '' }}>Đã tính — chờ gửi duyệt</option>
            <option value="hr_checked" {{ request('status')=='hr_checked' ? 'selected' : '' }}>Đã gửi duyệt — chờ Giám đốc</option>
            <option value="director_approved" {{ request('status')=='director_approved' ? 'selected' : '' }}>Giám đốc đã duyệt</option>
        </select>
        <button class="btn" type="submit">Tìm</button>
        <a class="btn" href="{{ route('accountant.payroll.index') }}">Xóa lọc</a>
    </form>

    @if($payrolls->count() === 0)
        <div class="empty">Chưa có bảng lương. Chờ HR xác nhận nguồn kỳ để hệ thống tự tính.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Tháng</th>
                    <th>Nhân viên</th>
                    <th>Tổng</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($payrolls as $p)
                    <tr>
                        <td>{{ $p->display_month }}</td>
                        <td>{{ optional($p->employee)->name }}</td>
                        <td>{{ number_format($p->total_salary ?? 0,0, '.', ',') }} VNĐ</td>
                        <td>
                            <span class="badge">{{ $workflow->statusLabel($p->status) }}</span>
                        </td>
                        <td style="text-align:right; display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap;">
                            <a class="btn" href="{{ route('accountant.payroll.show', $p) }}">Xem</a>
                            @if($workflow->actorCanSubmitToDirector(auth()->user(), $p))
                            <form method="POST" action="{{ route('payroll.review', $p) }}" style="display:inline;">
                                @csrf
                                <button class="btn primary" type="submit">Gửi duyệt</button>
                            </form>
                            @endif
                            @if(in_array($p->status, \App\Services\PayrollPaymentWorkflowService::recalculableStatuses(), true))
                            <form method="POST" action="{{ route('accountant.payroll.recalculate', $p) }}" style="display:inline;">
                                @csrf
                                <button class="btn" type="submit">Tính lại</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="pagination">{{ $payrolls->links() }}</div>
    @endif
</div>

@endsection
