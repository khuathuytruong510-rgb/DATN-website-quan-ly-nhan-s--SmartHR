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
    </div>
    <div class="actions">
        <a class="btn primary" href="{{ route('accountant.payroll.generate') }}">Tính lương</a>
    </div>
</div>

<div class="card">
    <form method="GET" class="row" style="display:flex; gap:12px; align-items:center; margin-bottom:12px;">
        <input name="q" placeholder="Tìm theo tên/ email/ tháng" value="{{ request('q') }}">
        <select name="status">
            <option value="">Tất cả trạng thái</option>
            <option value="calculated" {{ request('status')=='calculated' ? 'selected' : '' }}>Kế toán đã tính — chờ HR</option>
            <option value="hr_checked" {{ request('status')=='hr_checked' ? 'selected' : '' }}>HR đã kiểm tra — chờ Giám đốc</option>
            <option value="director_approved" {{ request('status')=='director_approved' ? 'selected' : '' }}>Giám đốc đã duyệt — chờ NV</option>
            <option value="payroll_issue" {{ request('status')=='payroll_issue' ? 'selected' : '' }}>Sự cố lương</option>
            <option value="employee_confirmed" {{ request('status')=='employee_confirmed' ? 'selected' : '' }}>NV đã xác nhận — chờ TT</option>
            <option value="paid" {{ request('status')=='paid' ? 'selected' : '' }}>Đã trả</option>
        </select>
        <button class="btn" type="submit">Tìm</button>
    </form>

    @if($payrolls->count() === 0)
        <div class="empty">Chưa có bảng lương. Hãy tạo bảng lương mới.</div>
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
                        <td>{{ optional($p->employee)->name }}<br></td>
                        <td>{{ number_format($p->total_salary ?? 0,0, '.', ',') }} VNĐ</td>
                        <td>
                            <span class="badge">{{ $workflow->statusLabel($p->status) }}</span>
                        </td>
                        <td style="text-align:right; display:flex; gap:8px; justify-content:flex-end;">
                            <a class="btn" href="{{ route('accountant.payroll.show', $p) }}">Xem</a>
                            @if(in_array($p->status, \App\Services\PayrollPaymentWorkflowService::recalculableStatuses(), true))
                            <form method="POST" action="{{ route('accountant.payroll.recalculate', $p) }}" style="display:inline;">
                                @csrf
                                <button class="btn" type="submit">Tính lại</button>
                            </form>
                            @elseif($workflow->canPay($p))
                            <a class="btn primary" href="{{ route('payroll.payment.show', $p) }}">Thanh toán</a>
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
