@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Chi tiết hợp đồng</h2>
            <p class="text-muted mb-0">Thông tin đầy đủ về hợp đồng, người ký và lịch sử gia hạn.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('contracts.index') }}">Quay lại</a>
            @if(auth()->user()?->is_admin || auth()->user()?->is_hr)
                <a class="btn btn-outline-secondary" href="{{ route('contracts.edit', $contract) }}">Sửa</a>
                <a class="btn btn-outline-info" href="{{ route('contracts.renew', $contract) }}">Gia hạn</a>
            @endif
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Thông tin hợp đồng</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><strong>Mã hợp đồng:</strong> {{ $contract->contract_code ?? '—' }}</div>
                        <div class="col-md-6"><strong>Loại hợp đồng:</strong> {{ $contract->contract_type ? ucfirst(str_replace('_', ' ', $contract->contract_type)) : '—' }}</div>
                        <div class="col-md-6"><strong>Ngày bắt đầu:</strong> {{ optional($contract->start_date)->format('d/m/Y') ?? '—' }}</div>
                        <div class="col-md-6"><strong>Ngày kết thúc:</strong> {{ optional($contract->end_date)->format('d/m/Y') ?? '—' }}</div>
                        <div class="col-md-6"><strong>Lương:</strong> {{ number_format($contract->salary ?? 0, 0, ',', '.') }} VNĐ</div>
                        <div class="col-md-6"><strong>Người tạo:</strong> {{ optional($contract->createdBy)->name ?? '—' }}</div>
                        <div class="col-md-12"><strong>Ghi chú:</strong> {{ $contract->notes ?? '—' }}</div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Thông tin nhân viên</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><strong>Nhân viên:</strong> {{ optional($contract->employee)->name ?? '—' }}</div>
                        <div class="col-md-6"><strong>Email:</strong> {{ optional($contract->employee)->email ?? '—' }}</div>
                        <div class="col-md-6"><strong>Chức vụ:</strong> {{ optional($contract->employee)->position ?? '—' }}</div>
                        <div class="col-md-6"><strong>Phòng ban:</strong> {{ optional(optional($contract->employee)->department)->name ?? '—' }}</div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Thông tin ký kết</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><strong>Nhân viên ký:</strong> {{ $contract->employee_signed_at ? optional($contract->employee_signed_at)->format('d/m/Y H:i') : 'Chưa ký' }}</div>
                        <div class="col-md-6"><strong>Giám đốc ký:</strong> {{ $contract->director_signed_at ? optional($contract->director_signed_at)->format('d/m/Y H:i') : 'Chưa ký' }}</div>
                        @if($contract->document_path)
                            <div class="col-md-12"><strong>File hợp đồng:</strong> <a href="{{ Storage::url($contract->document_path) }}" target="_blank">{{ $contract->document_name ?? 'Tải file' }}</a></div>
                        @endif
                    </div>
                </div>
            </div>

            @if($contract->parent_contract_id)
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Lịch sử gia hạn hợp đồng</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">Hợp đồng này được tạo từ hợp đồng gốc #{{ $contract->parent_contract_id }}.</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Trạng thái</h5>
                </div>
                <div class="card-body">
                    @php
                        $badge = match($contract->status) {
                            'waiting_employee', 'waiting_director' => 'warning',
                            'active' => 'success',
                            'expiring' => 'info',
                            'expired' => 'danger',
                            'cancelled' => 'secondary',
                            default => 'secondary',
                        };
                        $label = match($contract->status) {
                            'waiting_employee' => 'Chờ nhân viên ký',
                            'waiting_director' => 'Chờ giám đốc ký',
                            'active' => 'Có hiệu lực',
                            'expiring' => 'Sắp hết hạn',
                            'expired' => 'Hết hạn',
                            'cancelled' => 'Đã hủy',
                            default => 'Chờ xử lý',
                        };
                    @endphp
                    <div class="mb-3">
                        <span class="badge bg-{{ $badge }}">{{ $label }}</span>
                    </div>
                    <div class="mb-3">
                        <strong>Ngày còn lại:</strong> {{ $daysRemaining !== null ? $daysRemaining . ' ngày' : '—' }}
                    </div>
                    @if(auth()->user()?->is_admin || auth()->user()?->is_hr || optional($contract->employee)->email === auth()->user()?->email)
                        <form action="{{ route('contracts.sign', $contract) }}" method="POST">
                            @csrf
                            <input type="hidden" name="party" value="{{ optional($contract->employee)->email === auth()->user()?->email ? 'employee' : 'director' }}">
                            <button class="btn btn-primary w-100" type="submit">Ký hợp đồng</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
