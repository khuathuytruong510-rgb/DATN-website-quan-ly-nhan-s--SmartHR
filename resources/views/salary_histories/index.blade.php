@extends('layouts.app')

@section('title', 'Lịch sử lương')

@section('content')
<div class="page-head">
    <div>
        <h1>Lịch sử lương</h1>
        <p class="muted">Danh sách các thay đổi lương</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('payroll.index') }}">Bảng lương</a>
    </div>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Mã</th>
                <th>Nhân viên</th>
                <th>Kỳ</th>
                <th>Ngày áp dụng</th>
                <th>Thay đổi</th>
                <th>Mức cũ</th>
                <th>Mức mới</th>
                <th>Người cập nhật</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($histories as $h)
            <tr>
                <td>{{ $h->code ?? ('SH' . $h->id) }}</td>
                <td>{{ optional($h->employee)->name ?? 'Chưa có' }}</td>
                <td>{{ $h->period ?? '-' }}</td>
                <td>{{ $h->effective_date?->format('Y-m-d') ?? '-' }}</td>
                <td>{{ $h->change_type ?? '-' }}</td>
                <td>{{ number_format($h->old_salary ?? 0, 0, ',', '.') }}</td>
                <td>{{ number_format($h->new_salary ?? 0, 0, ',', '.') }}</td>
                <td>{{ optional($h->updatedBy)->name ?? '-' }}</td>
                <td><a class="btn" href="{{ route('salary_histories.show', $h) }}">Xem</a></td>
            </tr>
        @empty
            <tr><td colspan="9" class="muted">Chưa có lịch sử lương.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="pagination">
        {{ $histories->links() }}
    </div>
</div>

@endsection
