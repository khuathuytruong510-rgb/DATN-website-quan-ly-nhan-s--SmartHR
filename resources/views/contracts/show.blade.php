@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>Chi tiết hợp đồng</h1>
        <p class="muted">Xem thông tin thỏa thuận hợp đồng.</p>
    </div>
    <div>
        <a class="btn" href="{{ route('contracts.edit', $contract) }}">Sửa hợp đồng</a>
        <a class="btn link" href="{{ route('contracts.index') }}">Quay lại</a>
    </div>
</div>

<div class="card">
    <div class="grid two-cols">
        <div>
            <div class="field"><label>Nhân viên</label><div>{{ optional($contract->employee)->name }}</div></div>
            <div class="field"><label>Tiêu đề</label><div>{{ $contract->title }}</div></div>
            <div class="field"><label>Lương</label><div>{{ number_format($contract->salary, 0, ',', '.') }} VNĐ</div></div>
        </div>
        <div>
            <div class="field"><label>Ngày bắt đầu</label><div>{{ $contract->start_date }}</div></div>
            <div class="field"><label>Ngày kết thúc</label><div>{{ $contract->end_date }}</div></div>
            <div class="field"><label>Trạng thái</label><div>{{ ucfirst($contract->status) }}</div></div>
        </div>
    </div>
</div>
@endsection
