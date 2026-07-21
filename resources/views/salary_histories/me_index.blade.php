@extends('layouts.app')

@section('title', 'Lịch sử lương của tôi')

@section('content')
<div class="page-head">
    <div>
        <h1>Lịch sử lương của tôi</h1>
        <p class="muted">Các kỳ lương đã được thanh toán</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('me.payrolls') }}">Bảng lương của tôi</a>
    </div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
    @if($histories->isEmpty())
        <div class="empty" style="padding:24px;">Chưa có lịch sử lương. Sau khi công ty thanh toán, phiếu sẽ xuất hiện tại đây.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Kỳ</th>
                    <th>Ngày thanh toán</th>
                    <th>Loại</th>
                    <th class="text-end">Thực nhận</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($histories as $h)
                <tr>
                    <td>{{ $h->code ?? ('SH' . $h->id) }}</td>
                    <td>{{ $h->period ?? '—' }}</td>
                    <td>{{ $h->effective_date?->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $h->change_type ?? '—' }}</td>
                    <td class="text-end"><strong>{{ number_format($h->new_salary ?? 0, 0, ',', '.') }} ₫</strong></td>
                    <td><a class="btn" href="{{ route('me.salary_histories.show', $h) }}">Xem</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

@if(!$histories->isEmpty())
    <div style="margin-top:16px;">{{ $histories->links() }}</div>
@endif
@endsection
