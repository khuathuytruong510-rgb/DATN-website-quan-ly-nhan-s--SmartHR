@extends('layouts.app')

@section('title', 'Hợp đồng của tôi')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-1">Hợp đồng của tôi</h2>
            <p class="text-muted mb-0">Thông tin hợp đồng đang có hiệu lực và đã ký.</p>
        </div>
    </div>

    @if($contracts->isEmpty())
        <div class="alert alert-info">Không có hợp đồng nào.</div>
    @else
        <div class="row g-3">
            @foreach($contracts as $contract)
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title mb-1">{{ $contract->title ?? 'Hợp đồng' }}</h5>
                                    <p class="text-muted mb-2">Mã hợp đồng: {{ $contract->contract_code ?? '—' }}</p>
                                </div>
                                @php
                                    $statusLabel = match($contract->status) {
                                        'waiting_employee_signature', 'waiting_employee' => 'Chờ bạn ký',
                                        'waiting_director_signature', 'waiting_director' => 'Bạn đã ký — chờ Giám đốc ký',
                                        'active' => 'Có hiệu lực',
                                        'expired' => 'Hết hạn',
                                        'cancelled', 'terminated' => 'Đã hủy',
                                        default => 'Chờ hiệu lực',
                                    };
                                    $statusClass = match($contract->status) {
                                        'active' => 'success',
                                        'expired' => 'danger',
                                        default => 'warning',
                                    };
                                @endphp
                                <span class="badge bg-{{ $statusClass }}">{{ $statusLabel }}</span>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-4"><strong>Nhân viên:</strong> {{ $contract->employee->name ?? '—' }}</div>
                                <div class="col-md-4"><strong>Phòng ban:</strong> {{ optional($contract->employee->department)->name ?? '—' }}</div>
                                <div class="col-md-4"><strong>Ngày ký:</strong> {{ optional($contract->sign_date)->format('d/m/Y') ?? '—' }}</div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-4"><strong>Bắt đầu:</strong> {{ optional($contract->start_date)->format('d/m/Y') ?? '—' }}</div>
                                <div class="col-md-4"><strong>Kết thúc:</strong> {{ optional($contract->end_date)->format('d/m/Y') ?? '—' }}</div>
                                <div class="col-md-4"><strong>Lương cơ bản:</strong> {{ number_format($contract->base_salary ?? 0, 0, ',', '.') }} VNĐ</div>
                            </div>
                            @if(in_array($contract->status, ['waiting_employee_signature', 'waiting_employee'], true) && ! $contract->employee_signed_at)
                                <form method="POST" action="{{ route('me.contracts.sign', $contract) }}" class="mt-3">
                                    @csrf
                                    <button class="btn btn-primary" type="submit" data-confirm="Xác nhận ký hợp đồng này?">✍️ Ký hợp đồng</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
