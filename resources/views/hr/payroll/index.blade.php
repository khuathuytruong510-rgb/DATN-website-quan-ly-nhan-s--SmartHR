@extends('layouts.app')

@section('content')
<div class="content">
    <div class="page-head">
        <div>
            <h1>Danh sách Lương</h1>
            <p class="muted">Quản lý lương nhân viên</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert" style="background: #dcfce7; color: #166534; border-left: 4px solid #22c55e;">
            {{ session('success') }}
        </div>
    @endif

    @if($payrolls->count())
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Nhân viên</th>
                        <th>Tháng</th>
                        <th>Lương cơ bản</th>
                        <th>Phụ cấp</th>
                        <th>Khấu trừ</th>
                        <th>Tổng</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payrolls as $p)
                        <tr>
                            <td>{{ optional($p->employee)->name }}</td>
                            <td>{{ $p->month }}</td>
                            <td>{{ number_format($p->base_salary, 0, '.', ',') }} VNĐ</td>
                            <td>{{ number_format($p->allowance ?? 0, 0, '.', ',') }} VNĐ</td>
                            <td>{{ number_format($p->deduction ?? 0, 0, '.', ',') }} VNĐ</td>
                            <td><strong>{{ number_format($p->total_salary, 0, '.', ',') }} VNĐ</strong></td>
                            <td>
                                @if($p->status === 'pending')
                                    <span class="badge pending">Chờ duyệt</span>
                                @elseif($p->status === 'approved')
                                    <span class="badge" style="background: #dbeafe; color: #0369a1;">Đã duyệt</span>
                                @elseif($p->status === 'paid')
                                    <span class="badge" style="background: #dcfce7; color: #166534;">Đã thanh toán</span>
                                @endif
                            </td>
                            <td>
                                <div class="actions" style="gap: 6px;">
                                    <a href="{{ route('payroll.show', $p) }}" class="btn" style="padding: 6px 10px; font-size: 12px;">Xem</a>
                                    <form method="POST" action="{{ route('payroll.destroy', $p) }}" style="display: inline;" onsubmit="return confirm('Bạn có chắc muốn xóa bản ghi này?');">
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
            {{ $payrolls->links() }}
        </div>
    @else
        <div class="card">
            <div class="empty">
                <p>Không có bản ghi lương nào. <a href="{{ route('payroll.create') }}" style="color: #2563eb; font-weight: 700;">Thêm lương mới</a></p>
            </div>
        </div>
    @endif
</div>

<style>
    .content { max-width: 100%; }
    .page-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 22px; }
    h1 { margin: 0 0 8px; font-size: 32px; }
    .muted { color: #64748b; margin: 0; }
    .card { background: var(--panel); border: 1px solid var(--line); border-radius: 12px; padding: 24px; box-shadow: 0 10px 40px rgba(15, 23, 42, .06); }
    .alert { border-radius: 8px; padding: 13px 14px; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { text-align: left; padding: 14px 10px; border-bottom: 1px solid var(--line); }
    th { color: #64748b; font-size: 13px; text-transform: uppercase; font-weight: 700; }
    .badge { display: inline-flex; border-radius: 999px; padding: 5px 9px; font-size: 12px; font-weight: 700; background: #e0f2fe; color: #0369a1; }
    .badge.pending { background: #fef3c7; color: #92400e; }
    .btn { display: inline-flex; align-items: center; padding: 10px 14px; border-radius: 8px; border: none; cursor: pointer; font-weight: 700; text-decoration: none; background: #f8fafc; color: inherit; }
    .btn.primary { background: #2563eb; color: white; }
    .btn.danger { background: #fee2e2; color: #dc2626; }
    .actions { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
    .empty { background: #f8fafc; padding: 18px; border-radius: 8px; color: #64748b; }
</style>
@endsection
