@extends('layouts.app')

@section('content')
@include('components.module_header', [
    'title' => 'Hợp đồng',
    'subtitle' => 'Danh sách hợp đồng lao động và trạng thái ký kết.',
    'buttonText' => 'Tạo hợp đồng',
    'buttonRoute' => route('contracts.create'),
])

<div class="card">
    @if($contracts->count())
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Mã hợp đồng</th>
                    <th>Nhân viên</th>
                    <th>Chức vụ</th>
                    <th>Loại hợp đồng</th>
                    <th>Lương</th>
                    <th>Ngày bắt đầu</th>
                    <th>Ngày kết thúc</th>
                    <th>Người tạo</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contracts as $contract)
                    <tr>
                        <td>{{ $contract->contract_code ?? '—' }}</td>
                        <td>{{ optional($contract->employee)->name }}</td>
                        <td>{{ optional($contract->employee)->position ?? '—' }}</td>
                        <td>{{ $contract->contract_type ? ucfirst(str_replace('_', ' ', $contract->contract_type)) : '—' }}</td>
                        <td>{{ number_format($contract->salary ?? 0, 0, ',', '.') }} VNĐ</td>
                        <td>{{ optional($contract->start_date)->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ optional($contract->end_date)->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ optional($contract->createdBy)->name ?? '—' }}</td>
                        <td>
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
                            <span class="badge bg-{{ $badge }}">{{ $label }}</span>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('contracts.show', $contract) }}">Xem</a>
                                @if(auth()->user()?->is_admin || auth()->user()?->is_hr)
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('contracts.edit', $contract) }}">Sửa</a>
                                    <a class="btn btn-sm btn-outline-info" href="{{ route('contracts.renew', $contract) }}">Gia hạn</a>
                                @endif
                                @if(auth()->user()?->is_admin || auth()->user()?->is_hr || optional($contract->employee)->email === auth()->user()?->email)
                                    <form action="{{ route('contracts.sign', $contract) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="party" value="{{ optional($contract->employee)->email === auth()->user()?->email ? 'employee' : 'director' }}">
                                        <button class="btn btn-sm btn-outline-success" type="submit">Ký hợp đồng</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="pagination">{{ $contracts->links() }}</div>
    @else
        <div class="empty">Chưa có hợp đồng.</div>
    @endif
</div>
@endsection
