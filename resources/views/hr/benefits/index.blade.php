@extends('layouts.app')

@section('title', 'Phúc lợi')

@section('content')
<div class="content">
    <div class="page-head">
        <div>
            <h1>Phúc lợi</h1>
            <p class="muted">Quản lý phúc lợi, trợ cấp và bảo hiểm cho nhân viên.</p>
        </div>
        <div class="actions">
            <a class="btn primary" href="{{ route('benefits.create') }}">Thêm phúc lợi</a>
            <a class="btn" href="{{ route('benefits.assignments.index') }}">Gán phúc lợi</a>
        </div>
    </div>

    <div class="card filter-card">
        <form method="GET" action="{{ route('benefits.index') }}" class="filter-form">
            <div class="field-group">
                <label for="search">Tìm kiếm</label>
                <input id="search" name="search" value="{{ request('search') }}" placeholder="Mã/tiêu đề/nhân viên" />
            </div>
            <div class="field-group">
                <label for="type">Loại phúc lợi</label>
                <select id="type" name="type">
                    <option value="">Tất cả</option>
                    @foreach($filterTypes as $key => $label)
                        <option value="{{ $key }}" {{ request('type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field-group actions-row">
                <button type="submit" class="btn primary">Lọc</button>
                <a class="btn" href="{{ route('benefits.index') }}">Xóa lọc</a>
                <a class="btn" href="{{ route('benefits.export', request()->query()) }}">Xuất CSV</a>
            </div>
        </form>
    </div>

    @if(session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif

    @if($benefits->count())
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã</th>
                        <th>Nhân viên</th>
                        <th>Tiêu đề</th>
                        <th>Loại</th>
                        <th>Áp dụng cho</th>
                        <th>Đơn vị</th>
                        <th>Số tiền</th>
                        <th>Trạng thái ứng dụng</th>
                        <th>Trạng thái phê duyệt</th>
                        <th>Ngày hiệu lực</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($benefits as $index => $benefit)
                        <tr>
                            <td>{{ $benefits->firstItem() + $index }}</td>
                            <td>{{ $benefit->code ?? '---' }}</td>
                            <td>{{ optional($benefit->employee)->name ?? '---' }}</td>
                            <td>{{ $benefit->title }}</td>
                            <td>{{ ucfirst($benefit->type) }}</td>
                            <td>{{ $benefit->applies_to ?? '---' }}</td>
                            <td>{{ $benefit->unit ?? '---' }}</td>
                            <td>{{ $benefit->amount ? number_format($benefit->amount, 2) : '---' }}</td>
                            <td>{{ ucfirst($benefit->application_status) }}</td>
                            <td>{{ ucfirst($benefit->approval_status) }}</td>
                            <td>{{ optional($benefit->effective_date)->format('d/m/Y') ?? '---' }}</td>
                            <td>
                                <div class="actions" style="gap: 6px;">
                                    <a href="{{ route('benefits.show', $benefit) }}" class="btn" style="padding: 6px 10px; font-size: 12px;">Chi tiết</a>
                                    <a href="{{ route('benefits.edit', $benefit) }}" class="btn" style="padding: 6px 10px; font-size: 12px;">Sửa</a>
                                    @if($benefit->status === 'pending')
                                        <form method="POST" action="{{ route('benefits.approve', $benefit) }}" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn" style="padding: 6px 10px; font-size: 12px;">Duyệt</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('benefits.destroy', $benefit) }}" style="display: inline;" onsubmit="return confirm('Bạn có chắc muốn xóa phúc lợi này?');">
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
            {{ $benefits->links() }}
        </div>
    @else
        <div class="card">
            <div class="empty">
                Chưa có phúc lợi nào. <a href="{{ route('benefits.create') }}" style="color: #2563eb; font-weight: 700;">Thêm phúc lợi mới</a>
            </div>
        </div>
    @endif
</div>

<style>
    .content { max-width: 100%; }
    .page-head { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 22px; }
    .muted { color: #64748b; margin: 0; }
    .card { background: var(--panel); border: 1px solid var(--line); border-radius: 12px; padding: 24px; box-shadow: 0 10px 40px rgba(15, 23, 42, .06); }
    table { width: 100%; border-collapse: collapse; }
    th, td { text-align: left; padding: 14px 10px; border-bottom: 1px solid var(--line); }
    th { color: #64748b; font-size: 13px; text-transform: uppercase; font-weight: 700; }
    .btn { display: inline-flex; align-items: center; padding: 10px 14px; border-radius: 8px; border: none; cursor: pointer; font-weight: 700; text-decoration: none; background: #f8fafc; color: inherit; }
    .btn.primary { background: #2563eb; color: white; }
    .btn.danger { background: #fee2e2; color: #dc2626; }
    .actions { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
    .empty { background: #f8fafc; padding: 18px; border-radius: 8px; color: #64748b; }
</style>
@endsection
