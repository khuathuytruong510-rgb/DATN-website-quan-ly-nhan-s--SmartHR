@extends('layouts.app')

@section('title', 'Lịch sử lương của tôi')

@section('content')
<div class="page-head">
    <div>
        <h1>Lịch sử lương của tôi</h1>
        <p class="muted">Xem các thay đổi lương cho tài khoản của bạn</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('me.payrolls') }}">Bảng lương của tôi</a>
    </div>
</div>

<div class="card">
    @if($histories->isEmpty())
        <div class="empty">Chưa có lịch sử lương.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Kỳ</th>
                    <th>Ngày áp dụng</th>
                    <th>Thay đổi</th>
                    <th>Mức mới</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($histories as $h)
                <tr>
                    <td>{{ $h->code ?? ('SH' . $h->id) }}</td>
                    <td>{{ $h->period ?? '-' }}</td>
                    <td>{{ $h->effective_date?->format('Y-m-d') ?? '-' }}</td>
                    <td>{{ $h->change_type ?? '-' }}</td>
                    <td>{{ number_format($h->new_salary ?? 0, 0, ',', '.') }}</td>
                    <td><a class="btn" href="{{ route('salary_histories.show', $h) }}">Xem</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="pagination">{{ $histories->links() }}</div>
    @endif
</div>

@endsection
