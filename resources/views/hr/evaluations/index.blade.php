@extends('layouts.app')

@section('title', 'Đánh giá nhân viên')

@section('content')
<div class="content">
    <div class="page-head">
        <div>
            <h1>Đánh giá nhân viên theo tháng</h1>
            <p class="muted">Quản lý đánh giá hiệu suất của nhân viên</p>
        </div>
        <div class="actions">
            <a class="btn primary" href="{{ route('evaluations.create') }}">Thêm đánh giá</a>
            <a class="btn" href="#">Xuất Excel</a>
            <a class="btn" href="#">Xuất PDF</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif

    @if($evaluations->count())
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Nhân viên</th>
                        <th>Tháng</th>
                        <th>Điểm tổng</th>
                        <th>Phân loại</th>
                        <th>Người đánh giá</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($evaluations as $evaluation)
                        <tr>
                            <td>{{ optional($evaluation->employee)->name ?? '---' }}</td>
                            <td>{{ $evaluation->month }}</td>
                            <td>{{ $evaluation->score_total }}</td>
                            <td>{{ $evaluation->classification }}</td>
                            <td>{{ optional($evaluation->evaluator)->name ?? 'Hệ thống' }}</td>
                            <td>{{ ucfirst($evaluation->status) }}</td>
                            <td>
                                <div class="actions" style="gap: 6px;">
                                    <a href="{{ route('evaluations.show', $evaluation) }}" class="btn" style="padding: 6px 10px; font-size: 12px;">Chi tiết</a>
                                    <a href="{{ route('evaluations.edit', $evaluation) }}" class="btn" style="padding: 6px 10px; font-size: 12px;">Sửa</a>
                                    @if($evaluation->status === 'pending')
                                        <form method="POST" action="{{ route('evaluations.approve', $evaluation) }}" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn" style="padding: 6px 10px; font-size: 12px;">Duyệt</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('evaluations.destroy', $evaluation) }}" style="display: inline;" onsubmit="return confirm('Bạn có chắc muốn xóa đánh giá này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn danger" style="padding: 6px 10px; font-size: 12px;">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px;">
            {{ $evaluations->links() }}
        </div>
    @else
        <div class="card">
            <div class="empty">
                <p>Không có đánh giá nào. <a href="{{ route('evaluations.create') }}" style="color: #2563eb; font-weight: 700;">Tạo đánh giá mới</a></p>
            </div>
        </div>
    @endif
</div>

<style>
    .content { max-width: 100%; }
    .page-head { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 22px; }
    h1 { margin: 0 0 8px; font-size: 32px; }
    .muted { color: #64748b; margin: 0; }
    .card { background: var(--panel); border: 1px solid var(--line); border-radius: 12px; padding: 24px; box-shadow: 0 10px 40px rgba(15, 23, 42, .06); }
    table { width: 100%; border-collapse: collapse; }
    th, td { text-align: left; padding: 14px 10px; border-bottom: 1px solid var(--line); }
    th { color: #64748b; font-size: 13px; text-transform: uppercase; font-weight: 700; }
    .badge { display: inline-flex; border-radius: 999px; padding: 5px 9px; font-size: 12px; font-weight: 700; background: #e0f2fe; color: #0369a1; }
    .alert { border-radius: 8px; padding: 13px 14px; margin-bottom: 16px; }
    .btn { display: inline-flex; align-items: center; padding: 10px 14px; border-radius: 8px; border: none; cursor: pointer; font-weight: 700; text-decoration: none; background: #f8fafc; color: inherit; }
    .btn.primary { background: #2563eb; color: white; }
    .btn.danger { background: #fee2e2; color: #dc2626; }
    .actions { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
    .empty { background: #f8fafc; padding: 18px; border-radius: 8px; color: #64748b; }
</style>
@endsection
