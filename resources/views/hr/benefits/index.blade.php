@extends('layouts.app')

@section('title', 'Phúc lợi')

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
        <h1>Phúc lợi</h1>
        <p class="muted">Quản lý phúc lợi, trợ cấp và bảo hiểm cho nhân viên.</p>
    </div>
    <div class="page-actions">
        <a class="btn primary" href="{{ route('benefits.create') }}">Thêm phúc lợi</a>
        <a class="btn" href="{{ route('benefits.assignments.index') }}">Gán phúc lợi</a>
    </div>
</div>

<div class="card filter-card">
    <form method="GET" action="{{ route('benefits.index') }}" class="filter-form">
        <div class="field-group">
            <label class="form-label" for="search">Tìm kiếm</label>
            <input id="search" class="form-control" name="search" value="{{ request('search') }}" placeholder="Mã/tiêu đề/nhân viên">
        </div>
        <div class="field-group">
            <label class="form-label" for="type">Loại phúc lợi</label>
            <select id="type" class="form-select" name="type">
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
        <div class="table-responsive">
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
                            <td><code>{{ $benefit->code ?? '—' }}</code></td>
                            <td>{{ optional($benefit->employee)->name ?? '—' }}</td>
                            <td>{{ $benefit->title }}</td>
                            <td>{{ ucfirst($benefit->type) }}</td>
                            <td>{{ $benefit->applies_to ?? '—' }}</td>
                            <td>{{ $benefit->unit ?? '—' }}</td>
                            <td>{{ $benefit->amount ? number_format($benefit->amount, 0, ',', '.') : '—' }}</td>
                            <td><span class="badge {{ $statusClass($benefit->application_status) }}">{{ $statusLabel($benefit->application_status) }}</span></td>
                            <td><span class="badge {{ $statusClass($benefit->approval_status) }}">{{ $statusLabel($benefit->approval_status) }}</span></td>
                            <td>{{ optional($benefit->effective_date)->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                <div class="table-actions">
                                    <a href="{{ route('benefits.show', $benefit) }}" class="btn btn-sm">Chi tiết</a>
                                    <a href="{{ route('benefits.edit', $benefit) }}" class="btn btn-sm">Sửa</a>
                                    @if($benefit->status === 'pending')
                                        <form method="POST" action="{{ route('benefits.approve', $benefit) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm success">Duyệt</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('benefits.destroy', $benefit) }}" onsubmit="return confirm('Bạn có chắc muốn xóa phúc lợi này?');">
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
        <div class="pagination">{{ $benefits->links() }}</div>
    </div>
@else
    <div class="card">
        <div class="empty">
            Chưa có phúc lợi nào. <a href="{{ route('benefits.create') }}">Thêm phúc lợi mới</a>
        </div>
    </div>
@endif
@endsection
