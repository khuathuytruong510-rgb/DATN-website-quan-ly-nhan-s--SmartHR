@extends('layouts.app')

@section('title', 'Đánh giá của tôi')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('me.dashboard') }}">Dashboard</a></li>
<li>Đánh giá</li>
@endsection
<div class="content">
    <div class="page-head">
        <div>
            <h1>Đánh giá của tôi</h1>
        </div>
    </div>

    @if($evaluations->count())
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Tháng</th>
                        <th>Điểm tổng</th>
                        <th>Phân loại</th>
                        <th>Tóm tắt</th>
                        <th>Người đánh giá</th>
                        <th>Ngày cập nhật</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($evaluations as $evaluation)
                        <tr>
                            <td>{{ $evaluation->month }}</td>
                            <td>{{ $evaluation->score_total }}</td>
                            <td>{{ $evaluation->classification }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($evaluation->summary ?? $evaluation->comments, 100) }}</td>
                            <td>{{ optional($evaluation->evaluator)->name ?? 'Hệ thống' }}</td>
                            <td>{{ $evaluation->updated_at->format('d/m/Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="card">
            <div class="empty">
                Hiện chưa có đánh giá nào cho bạn.
            </div>
        </div>
    @endif
</div>

<style>
    .content { max-width: 100%; }
    .page-head { margin-bottom: 22px; }
    .muted { color: #64748b; margin: 0; }
    .card { background: var(--panel); border: 1px solid var(--line); border-radius: 12px; padding: 24px; box-shadow: 0 10px 40px rgba(15, 23, 42, .06); }
    table { width: 100%; border-collapse: collapse; }
    th, td { text-align: left; padding: 14px 10px; border-bottom: 1px solid var(--line); }
    th { color: #64748b; font-size: 13px; text-transform: uppercase; font-weight: 700; }
    .empty { background: #f8fafc; padding: 18px; border-radius: 8px; color: #64748b; }
</style>
@endsection
