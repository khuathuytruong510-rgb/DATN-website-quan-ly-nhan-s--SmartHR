@extends('layouts.app')

@section('title', 'Phòng ban - SmartHR')

@section('content')
    <div class="page-head">
        <div>
            <h1>Phòng ban</h1>
            <p class="muted">Xóa phòng ban cần Giám đốc duyệt. Còn nhân viên thì phải chuyển sang phòng khác hoặc đề nghị xóa nhân viên trước.</p>
        </div>
        @if(auth()->user()?->canManageHr())
        <div style="display:flex;gap:8px;">
            <a class="btn" href="{{ route('transfers.create') }}">Điều chuyển NV</a>
            <a class="btn primary" href="{{ route('departments.create') }}">Thêm phòng ban</a>
        </div>
        @endif
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th style="width:50px">STT</th>
                    <th>Tên phòng ban</th>
                    <th style="width:120px">Mã phòng ban</th>
                    <th>Mô tả chức năng</th>
                    <th style="width:90px">Nhân viên</th>
                    <th style="width:220px">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($departments as $i => $department)
                    @php $pendingDeletionId = ($pendingDepartmentDeletions ?? collect())[$department->id] ?? null; @endphp
                    <tr>
                        <td>{{ $departments->firstItem() + $i }}</td>
                        <td><strong>{{ $department->name }}</strong></td>
                        <td><span class="badge bg-secondary">{{ $department->code }}</span></td>
                        <td>{{ $department->description ?: '-' }}</td>
                        <td>{{ $department->employees_count }}</td>
                        <td>
                            <div class="actions">
                                <a class="btn link" href="{{ route('departments.show', $department) }}">Xem</a>
                                @if(auth()->user()?->canManageHr())
                                <a class="btn" href="{{ route('departments.edit', $department) }}">Sửa</a>
                                @if($pendingDeletionId)
                                    <a class="btn" href="{{ route('deletion_requests.show', $pendingDeletionId) }}">Chờ GĐ duyệt xóa</a>
                                @elseif($department->isBoard())
                                    <span class="muted">Không điều chuyển / xóa</span>
                                @elseif($department->employees_count > 0)
                                    <a class="btn" href="{{ route('transfers.create', ['from' => $department->id]) }}">Điều chuyển</a>
                                    <a class="btn danger" href="{{ route('deletion_requests.create_department', $department) }}">Chuyển NV / xóa</a>
                                @else
                                    <a class="btn danger" href="{{ route('deletion_requests.create_department', $department) }}">Đề nghị xóa</a>
                                @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6"><div class="empty">Chưa có phòng ban.</div></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">{{ $departments->links() }}</div>
    </div>
@endsection
