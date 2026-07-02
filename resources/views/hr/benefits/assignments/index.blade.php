@extends('layouts.app')

@section('title', 'Gán phúc lợi')

@section('content')
<div class="content">
    <div class="page-head">
        <div>
            <h1>Gán phúc lợi</h1>
            <p class="muted">Quản lý danh sách phúc lợi đã gán cho nhân viên.</p>
        </div>
        <div class="actions">
            <a class="btn primary" href="{{ route('benefits.assignments.create') }}">Gán phúc lợi mới</a>
            <a class="btn" href="{{ route('benefits.index') }}">Quay lại danh sách phúc lợi</a>
        </div>
    </div>

    <div class="card filter-card">
        <form method="GET" action="{{ route('benefits.assignments.index') }}" class="filter-form">
            <div class="field-group">
                <label for="search">Tìm kiếm</label>
                <input id="search" name="search" value="{{ request('search') }}" placeholder="Nhân viên hoặc tên phúc lợi" />
            </div>
            <div class="field-group actions-row">
                <button type="submit" class="btn primary">Lọc</button>
                <a class="btn" href="{{ route('benefits.assignments.index') }}">Xóa lọc</a>
            </div>
        </form>
    </div>

    @if(session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif

    @if($assignments->count())
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Nhân viên</th>
                        <th>Phúc lợi</th>
                        <th>Mã</th>
                        <th>Loại</th>
                        <th>Ngày áp dụng</th>
                        <th>Trạng thái</th>
                        <th>Ghi chú</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assignments as $index => $assignment)
                        <tr>
                            <td>{{ $assignments->firstItem() + $index }}</td>
                            <td>{{ optional($assignment->employee)->name ?? '---' }}</td>
                            <td>{{ optional($assignment->benefit)->title ?? '---' }}</td>
                            <td>{{ optional($assignment->benefit)->code ?? '---' }}</td>
                            <td>{{ optional($assignment->benefit)->type ? ucfirst(optional($assignment->benefit)->type) : '---' }}</td>
                            <td>{{ optional($assignment->applied_at)->format('d/m/Y') ?? '---' }}</td>
                            <td>{{ ucfirst($assignment->status) }}</td>
                            <td>{{ Illuminate\Support\Str::limit($assignment->notes, 100) }}</td>
                            <td>
                                <div class="actions" style="gap: 6px;">
                                    <a href="{{ route('benefits.assignments.edit', $assignment) }}" class="btn" style="padding: 6px 10px; font-size: 12px;">Sửa</a>
                                    <form method="POST" action="{{ route('benefits.assignments.destroy', $assignment) }}" style="display: inline;" onsubmit="return confirm('Bạn có chắc muốn xóa gán phúc lợi này?');">
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
            {{ $assignments->links() }}
        </div>
    @else
        <div class="card">
            <div class="empty">
                Chưa có gán phúc lợi nào.
            </div>
        </div>
    @endif
</div>

<style>
    .content { max-width: 100%; }
    .page-head { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 22px; }
    .muted { color: #64748b; margin: 0; }
    .card { background: var(--panel); border: 1px solid var(--line); border-radius: 12px; padding: 24px; box-shadow: 0 10px 40px rgba(15, 23, 42, .06); }
    .filter-card { margin-bottom: 20px; }
    .filter-form { display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; }
    .field-group { flex: 1; min-width: 220px; }
    label { display: block; font-weight: 700; margin-bottom: 8px; }
    input, select { width: 100%; padding: 11px 14px; border: 1px solid #cbd5e1; border-radius: 8px; }
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
