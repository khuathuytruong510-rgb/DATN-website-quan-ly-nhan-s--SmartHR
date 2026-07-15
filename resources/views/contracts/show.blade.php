@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>Chi tiết hợp đồng</h1>
        <p class="muted">Xem thông tin thỏa thuận hợp đồng.</p>
    </div>
    <div>
        <div class="actions">
            <a class="btn" href="{{ route('contracts.index') }}">Quay lại</a>
            <a class="btn" href="{{ route('contracts.edit', $contract) }}">Sửa</a>
            <a class="btn" href="{{ route('contracts.create', ['employee_id' => $contract->employee_id, 'renew_from' => $contract->id]) }}">Gia hạn</a>
            @if($contract->pdf_file)
                <a class="btn primary" target="_blank" href="{{ asset('storage/' . $contract->pdf_file) }}">In PDF</a>
            @endif
        </div>
    </div>
</div>

<div class="card">
    <div class="grid two-cols">
        <div>
            <h3>Thông tin nhân viên</h3>
            <div class="field"><label>Họ tên</label><div>{{ optional($contract->employee)->name }}</div></div>
            <div class="field"><label>Email</label><div>{{ optional($contract->employee)->email }}</div></div>
            <div class="field"><label>Chức vụ</label><div>{{ optional($contract->employee)->position }}</div></div>
            <div class="field"><label>Phòng ban</label><div>{{ optional($contract->employee->department)->name }}</div></div>

            <h3>Thông tin hợp đồng</h3>
            <div class="field"><label>Tiêu đề</label><div>{{ $contract->title }}</div></div>
            <div class="field"><label>Người đại diện công ty</label><div>{{ $contract->company_representative }}</div></div>
            <div class="field"><label>Người ký</label><div>{{ $contract->signer }}</div></div>
            <div class="field"><label>Ghi chú</label><div class="muted">{{ $contract->notes }}</div></div>
        </div>
        <div>
            <h3>Lương & Phụ cấp</h3>
            <div class="field"><label>Lương cơ bản</label><div>{{ number_format($contract->base_salary ?? 0, 0, ',', '.') }} VNĐ</div></div>
            <div class="field"><label>Phụ cấp</label><div>{{ number_format($contract->allowance ?? 0, 0, ',', '.') }} VNĐ</div></div>
            <div class="field"><label>Lương thử việc</label><div>{{ number_format($contract->probation_salary ?? 0, 0, ',', '.') }} VNĐ</div></div>

            <h3>Trạng thái & Thời hạn</h3>
            <div class="field"><label>Trạng thái</label><div><span class="{{ $statusBadge }}">{{ ucfirst($contract->status) }}</span></div></div>
            <div class="field"><label>Ngày bắt đầu</label><div>{{ $contract->start_date?->format('Y-m-d') }}</div></div>
            <div class="field"><label>Ngày kết thúc</label><div>{{ $contract->end_date?->format('Y-m-d') }}</div></div>
            <div class="field"><label>Số ngày còn lại</label><div>{{ isset($daysRemaining) ? $daysRemaining . ' ngày' : 'N/A' }}</div></div>

            <h3>Phụ cấp & Điều khoản</h3>
            @if(isset($benefits) && $benefits->isNotEmpty())
                <table>
                    <thead><tr><th>Phụ cấp</th><th>Số tiền</th><th>Trạng thái</th></tr></thead>
                    <tbody>
                    @foreach($benefits as $b)
                        <tr>
                            <td>{{ optional($b->benefit)->title ?? '(không tên)' }}</td>
                            <td>{{ number_format(optional($b->benefit)->amount ?? 0, 0, ',', '.') }}</td>
                            <td>{{ ucfirst($b->status) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty">Không có phụ cấp liên quan.</div>
            @endif

            @if($contract->pdf_file)
                <h3>Tệp PDF hợp đồng</h3>
                <div class="field"><a class="btn link" target="_blank" href="{{ asset('storage/' . $contract->pdf_file) }}">Mở PDF</a></div>
            @endif
        </div>
    </div>
</div>
@endsection
