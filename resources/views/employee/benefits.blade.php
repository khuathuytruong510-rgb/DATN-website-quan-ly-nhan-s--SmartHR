@extends('layouts.app')

@section('title', 'Phúc lợi của tôi')

@section('content')
<div class="content">
    <div class="page-head">
        <div>
            <h1>Phúc lợi của tôi</h1>
            <p class="muted">Danh sách phúc lợi và trợ cấp bạn có thể sử dụng.</p>
        </div>
    </div>

    @if($benefits->count())
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã</th>
                        <th>Tiêu đề</th>
                        <th>Loại</th>
                        <th>Áp dụng cho</th>
                        <th>Đơn vị</th>
                        <th>Số tiền</th>
                        <th>Trạng thái ứng dụng</th>
                        <th>Trạng thái phê duyệt</th>
                        <th>Hiệu lực</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($benefits as $index => $benefit)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $benefit->code ?? '---' }}</td>
                            <td>{{ $benefit->title }}</td>
                            <td>{{ ucfirst($benefit->type) }}</td>
                            <td>{{ $benefit->applies_to ?? '---' }}</td>
                            <td>{{ $benefit->unit ?? '---' }}</td>
                            <td>{{ $benefit->amount ? number_format($benefit->amount, 2) : '---' }}</td>
                            <td>{{ ucfirst($benefit->application_status) }}</td>
                            <td>{{ ucfirst($benefit->approval_status) }}</td>
                            <td>{{ optional($benefit->effective_date)->format('d/m/Y') ?? '---' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="card">
            <div class="empty">
                Hiện chưa có phúc lợi nào dành cho bạn.
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