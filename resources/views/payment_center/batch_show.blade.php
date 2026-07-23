@extends('layouts.app')

@section('title', 'Chi tiết lô thanh toán #' . $batch->code)

@section('content')

@section('breadcrumb')
<li><a href="{{ route('payment_center.dashboard') }}">Trung tâm thanh toán</a></li>
<li><a href="{{ route('payment_center.batches.index') }}">Lô thanh toán</a></li>
<li>{{ $batch->code }}</li>
@endsection

<div class="page-head">
    <div>
        <h1>Lô thanh toán #{{ $batch->code }}</h1>
        <p class="muted">{{ $batch->name }}</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('payment_center.batches.index') }}">Quay lại</a>
        @if($batch->status === 'pending')
            <form method="POST" action="{{ route('payment_center.batches.destroy', $batch) }}" style="display:inline;">
                @csrf
                @method('DELETE')
                <button class="btn danger" type="submit" onclick="return confirm('Xác nhận xóa lô thanh toán này?')">Xóa lô</button>
            </form>
        @endif
    </div>
</div>

<div class="grid two-cols" style="margin-bottom:20px;">
    <div class="card">
        <h1 style="font-size:18px; margin-bottom:12px;">Thông tin lô</h1>
        <table>
            <tr>
                <td class="muted" style="width:150px;">Mã lô</td>
                <td><strong>{{ $batch->code }}</strong></td>
            </tr>
            <tr>
                <td class="muted">Tên lô</td>
                <td>{{ $batch->name }}</td>
            </tr>
            <tr>
                <td class="muted">Tháng/Năm</td>
                <td>{{ sprintf('%02d/%04d', $batch->month, $batch->year) }}</td>
            </tr>
            <tr>
                <td class="muted">Số phiếu</td>
                <td>{{ $batch->total_items }}</td>
            </tr>
            <tr>
                <td class="muted">Tổng tiền</td>
                <td><strong>{{ number_format($batch->total_amount, 0) }} VNĐ</strong></td>
            </tr>
            <tr>
                <td class="muted">Đã thanh toán</td>
                <td>{{ number_format($batch->total_paid, 0) }} VNĐ</td>
            </tr>
            <tr>
                <td class="muted">Trạng thái</td>
                <td>
                    @if($batch->status === 'pending')
                        <span class="badge pending">Chờ xử lý</span>
                    @elseif($batch->status === 'processing')
                        <span class="badge" style="background:#dbeafe;color:#1e40af;">Đang xử lý</span>
                    @elseif($batch->status === 'completed')
                        <span class="badge">Hoàn thành</span>
                    @else
                        <span class="badge" style="background:#fee2e2;color:#dc2626;">Đã hủy</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="muted">Người tạo</td>
                <td>{{ $batch->createdBy->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="muted">Ngày tạo</td>
                <td>{{ $batch->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            @if($batch->approvedBy)
                <tr>
                    <td class="muted">Người duyệt</td>
                    <td>{{ $batch->approvedBy->name }}</td>
                </tr>
            @endif
        </table>
    </div>

    <div class="card">
        <div style="text-align:center; padding:20px 0;">
            <div class="muted" style="margin-bottom:8px;">Tổng tiền lô</div>
            <div style="font-size:36px; font-weight:800; color:#2563eb;">{{ number_format($batch->total_amount, 0) }}</div>
            <div class="muted">VNĐ</div>
        </div>

        @if($batch->status === 'pending')
            <div style="border-top:1px solid #e5e7eb; padding-top:16px; margin-top:16px;">
                <h1 style="font-size:16px; margin-bottom:12px;">Xử lý thanh toán</h1>
                <form method="POST" action="{{ route('payment_center.batches.process', $batch) }}">
                    @csrf
                    <div class="field">
                        <label>Hình thức thanh toán <span style="color:#dc2626;">*</span></label>
                        <select name="payment_method" required>
                            <option value="">-- Chọn hình thức --</option>
                            <option value="bank_transfer">Chuyển khoản ngân hàng</option>
                            <option value="cash">Tiền mặt</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Ngân hàng (nếu chuyển khoản)</label>
                        <input type="text" name="bank" placeholder="Tên ngân hàng">
                    </div>
                    <div class="field">
                        <label>Ghi chú</label>
                        <textarea name="notes" rows="3" placeholder="Ghi chú xử lý (không bắt buộc)"></textarea>
                    </div>
                    <button class="btn primary" type="submit" onclick="return confirm('Xác nhận xử lý thanh toán cho toàn bộ lô này?')">
                        Xử lý thanh toán
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="page-head" style="margin-bottom:12px;">
        <h1 style="font-size:18px;">Danh sách phiếu trong lô</h1>
    </div>
    @if($batch->payments && $batch->payments->count())
        <table>
            <thead>
                <tr>
                    <th>Mã phiếu</th>
                    <th>Nhân viên</th>
                    <th>Tháng/Năm</th>
                    <th>Thực lĩnh</th>
                    <th>Trạng thái</th>
                    <th>Ngày TT</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($batch->payments as $p)
                    <tr>
                        <td><a href="{{ route('payment_center.payments.show', $p) }}">{{ $p->code }}</a></td>
                        <td>
                            <strong>{{ $p->employee->name ?? '-' }}</strong><br>
                            <small class="muted">{{ $p->employee->employee_code ?? '' }}</small>
                        </td>
                        <td>{{ sprintf('%02d/%04d', $p->month, $p->year) }}</td>
                        <td><strong>{{ number_format($p->net, 0) }} VNĐ</strong></td>
                        <td>
                            @if($p->status === 'paid')
                                <span class="badge">Đã TT</span>
                            @else
                                <span class="badge pending">Chờ TT</span>
                            @endif
                        </td>
                        <td class="muted">{{ $p->paid_at ? $p->paid_at->format('d/m/Y') : '—' }}</td>
                        <td>
                            <a class="btn" href="{{ route('payment_center.payments.show', $p) }}">Xem</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">Lô này chưa có phiếu thanh toán nào.</div>
    @endif
</div>

@endsection
