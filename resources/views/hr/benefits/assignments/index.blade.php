@extends('layouts.app')

@section('title', 'Gán phúc lợi')

@section('content')
@php
    $statusLabel = function (?string $status): string {
        return match (strtolower((string) $status)) {
            'active' => 'Đang áp dụng',
            'inactive' => 'Ngưng áp dụng',
            'pending' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            default => $status ? ucfirst($status) : '—',
        };
    };
    $statusClass = function (?string $status): string {
        return match (strtolower((string) $status)) {
            'active', 'approved' => 'ok',
            'pending' => 'pending',
            'inactive', 'rejected' => 'danger',
            default => 'muted',
        };
    };
@endphp
<div class="page-head">
    <div>
        <h1>Gán phúc lợi</h1>
        <p class="muted">Quản lý danh sách phúc lợi đã gán cho nhân viên.</p>
    </div>
    <div class="page-actions">
        <a class="btn primary" href="{{ route('benefits.assignments.create') }}">Gán phúc lợi mới</a>
        <a class="btn" href="{{ route('benefits.index') }}">Quay lại danh sách phúc lợi</a>
    </div>
</div>

<div class="card filter-card">
    <form method="GET" action="{{ route('benefits.assignments.index') }}" class="filter-form">
        <div class="field-group">
            <label class="form-label" for="search">Tìm kiếm</label>
            <input id="search" class="form-control" name="search" value="{{ request('search') }}" placeholder="Nhân viên hoặc tên phúc lợi">
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
        <div class="table-responsive">
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
                            <td>{{ optional($assignment->employee)->name ?? '—' }}</td>
                            <td>{{ optional($assignment->benefit)->title ?? '—' }}</td>
                            <td><code>{{ optional($assignment->benefit)->code ?? '—' }}</code></td>
                            <td>{{ optional($assignment->benefit)->type ? ucfirst(optional($assignment->benefit)->type) : '—' }}</td>
                            <td>{{ optional($assignment->applied_at)->format('d/m/Y') ?? '—' }}</td>
                            <td><span class="badge {{ $statusClass($assignment->status) }}">{{ $statusLabel($assignment->status) }}</span></td>
                            <td>{{ Illuminate\Support\Str::limit($assignment->notes, 100) }}</td>
                            <td>
                                <div class="actions" style="gap: 6px;">
                                    <a href="{{ route('benefits.assignments.edit', $assignment) }}" class="btn" style="padding: 6px 10px; font-size: 12px;">Sửa</a>
                                    <form method="POST" action="{{ route('benefits.assignments.destroy', $assignment) }}" style="display: inline;" data-confirm="Bạn có chắc muốn xóa gán phúc lợi này?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm danger">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $assignments->links() }}</div>
    </div>
@else
    <div class="card">
        <div class="empty">Chưa có gán phúc lợi nào.</div>
    </div>
@endif
@endsection
