@extends('layouts.app')

@section('title', 'Đối soát thanh toán')

@section('content')

@section('breadcrumb')
<li><a href="{{ route('payment_center.dashboard') }}">Trung tâm thanh toán</a></li>
<li>Đối soát</li>
@endsection

<div class="page-head">
    <div>
        <h1>Đối soát thanh toán</h1>
        <p class="muted">Kiểm tra và đối soát các khoản thanh toán lương</p>
    </div>
</div>

<div class="card" style="margin-bottom:20px;">
    <form method="GET" action="{{ route('payment_center.reconcile') }}" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
        <div class="field" style="margin-bottom:0; min-width:160px;">
            <label>Trạng thái đối soát</label>
            <select name="reconciliation_status">
                <option value="">Tất cả</option>
                <option value="pending" {{ request('reconciliation_status') == 'pending' ? 'selected' : '' }}>Chưa đối soát</option>
                <option value="reconciled" {{ request('reconciliation_status') == 'reconciled' ? 'selected' : '' }}>Đã đối soát</option>
                <option value="discrepancy" {{ request('reconciliation_status') == 'discrepancy' ? 'selected' : '' }}>Chênh lệch</option>
            </select>
        </div>
        <div class="field" style="margin-bottom:0; min-width:130px;">
            <label>Tháng</label>
            <select name="month">
                <option value="">Tất cả</option>
                @for($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>Tháng {{ $i }}</option>
                @endfor
            </select>
        </div>
        <div class="field" style="margin-bottom:0; min-width:130px;">
            <label>Năm</label>
            <select name="year">
                <option value="">Tất cả</option>
                @for($y = now()->year - 3; $y <= now()->year + 1; $y++)
                    <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </div>
        <button class="btn primary" type="submit">Lọc</button>
        <a class="btn" href="{{ route('payment_center.reconcile') }}">Đặt lại</a>
    </form>
</div>

<div class="card">
    @if($payments && $payments->count())
        <table>
            <thead>
                <tr>
                    <th>Nhân viên</th>
                    <th>Số tiền</th>
                    <th>Ngày thanh toán</th>
                    <th>Trạng thái đối soát</th>
                    <th>Người đối soát</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $p)
                    <tr>
                        <td>
                            <strong>{{ $p->employee->name ?? '-' }}</strong><br>
                            <small class="muted">{{ $p->employee->employee_code ?? '' }}</small>
                        </td>
                        <td>{{ number_format($p->net, 0) }} VNĐ</td>
                        <td class="muted">{{ $p->paid_at ? $p->paid_at->format('d/m/Y') : '—' }}</td>
                        <td>
                            @if($p->reconciliation_status === 'reconciled')
                                <span class="badge">Đã đối soát</span>
                            @elseif($p->reconciliation_status === 'discrepancy')
                                <span class="badge" style="background:#fee2e2;color:#dc2626;">Chênh lệch</span>
                            @else
                                <span class="badge pending">Chưa đối soát</span>
                            @endif
                        </td>
                        <td>{{ $p->reconciledBy->name ?? '—' }}</td>
                        <td>
                            @if($p->reconciliation_status !== 'reconciled' && $p->status === 'paid')
                                <div class="actions">
                                    <form method="POST" action="{{ route('payment_center.reconcile.store', $p) }}" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="reconciliation_status" value="reconciled">
                                        <input type="hidden" name="notes" value="">
                                        <button class="btn primary" type="submit" onclick="return confirm('Xác nhận đối soát khớp khoản thanh toán này?')">
                                            Đối soát
                                        </button>
                                    </form>
                                    <button class="btn danger" type="button" onclick="openDiscrepancy({{ $p->id }})">
                                        Chênh lệch
                                    </button>
                                </div>
                            @elseif($p->reconciliation_status === 'discrepancy')
                                <span class="muted">Đã báo chênh lệch</span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="pagination">{{ $payments->links() }}</div>
    @else
        <div class="empty">Không có khoản thanh toán nào cần đối soát.</div>
    @endif
</div>

@foreach($payments as $p)
    @if($p->reconciliation_status !== 'reconciled' && $p->status === 'paid')
        <div id="discrepancy-modal-{{ $p->id }}" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); z-index:9999; display:none; align-items:center; justify-content:center;">
            <div style="background:#fff; border-radius:12px; padding:24px; width:min(440px, 90%); box-shadow:0 20px 60px rgba(0,0,0,0.2);">
                <h2 style="margin:0 0 16px; font-size:20px;">Báo chênh lệch - {{ $p->employee->name ?? '' }}</h2>
                <form method="POST" action="{{ route('payment_center.reconcile.store', $p) }}">
                    @csrf
                    <input type="hidden" name="reconciliation_status" value="discrepancy">
                    <div class="field">
                        <label>Lý do chênh lệch <span style="color:#dc2626;">*</span></label>
                        <textarea name="notes" rows="4" required placeholder="Mô tả lý do chênh lệch..."></textarea>
                    </div>
                    <div class="actions">
                        <button class="btn danger" type="submit">Gửi báo chênh lệch</button>
                        <button class="btn" type="button" onclick="closeDiscrepancy({{ $p->id }})">Hủy</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@endsection

@push('scripts')
<script>
function openDiscrepancy(id) {
    var modal = document.getElementById('discrepancy-modal-' + id);
    if (modal) { modal.style.display = 'flex'; }
}
function closeDiscrepancy(id) {
    var modal = document.getElementById('discrepancy-modal-' + id);
    if (modal) { modal.style.display = 'none'; }
}
</script>
@endpush
