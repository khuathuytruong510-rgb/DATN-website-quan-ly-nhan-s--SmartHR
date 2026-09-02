@extends('layouts.app')

@section('title', 'Lịch sử lương')

@section('content')
<div class="page-head">
    <div>
        <h1>Lịch sử lương</h1>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('payroll.index') }}">Bảng lương</a>
    </div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
    <table>
        <thead>
            <tr>
                <th>Mã</th>
                <th>Nhân viên</th>
                <th>Kỳ</th>
                <th>Ngày</th>
                <th>Loại</th>
                <th class="text-end">Lương CB</th>
                <th class="text-end">Thực nhận</th>
                <th>Người cập nhật</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($histories as $h)
            <tr>
                <td>{{ $h->code ?? ('SH' . $h->id) }}</td>
                <td>{{ optional($h->employee)->name ?? '—' }}</td>
                <td>{{ $h->period ?? '—' }}</td>
                <td>{{ $h->effective_date?->format('d/m/Y') ?? '—' }}</td>
                <td>
                    @if($h->change_type === \App\Models\SalaryHistory::CHANGE_PAYMENT)
                        <span class="badge" style="background:#dcfce7;color:#166534;">{{ $h->change_type }}</span>
                    @else
                        {{ $h->change_type ?? '—' }}
                    @endif
                </td>
                <td class="text-end">{{ number_format($h->old_salary ?? 0, 0, ',', '.') }}</td>
                <td class="text-end"><strong>{{ number_format($h->new_salary ?? 0, 0, ',', '.') }}</strong></td>
                <td>{{ optional($h->updatedBy)->name ?? '—' }}</td>
                <td><a class="btn" href="{{ route('salary_histories.show', $h) }}">Xem</a></td>
            </tr>
        @empty
            <tr><td colspan="9"><div class="empty">Chưa có lịch sử lương. Lịch sử được tạo sau khi thanh toán phiếu lương.</div></td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:16px;">{{ $histories->links() }}</div>
@endsection
