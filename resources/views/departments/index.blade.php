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

    @if($departments->count())
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(290px, 1fr)); gap:16px;">
            @foreach ($departments as $department)
                <div class="card" style="display:flex; flex-direction:column; gap:12px; padding:18px 18px 14px; transition:transform .15s ease, box-shadow .15s ease;">
                    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:10px;">
                        <div style="min-width:0;">
                            <a href="{{ route('departments.show', $department) }}" style="font-weight:800; font-size:17px; text-decoration:none; color:var(--text); display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                {{ $department->name }}
                            </a>
                            <span class="badge bg-secondary" style="margin-top:6px;">{{ $department->code }}</span>
                        </div>
                        <div style="text-align:right; font-size:12px; color:var(--muted); flex-shrink:0;">
                            <div style="font-weight:800; color:var(--text); font-size:15px;">{{ $department->employees_count ?? $department->employee_count ?? 0 }}</div>
                            <div>nhân viên</div>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:13px;">
                        <div style="background:#f8fafc; border:1px solid var(--line); border-radius:10px; padding:8px 10px;">
                            <div style="color:var(--muted); font-size:12px;">Trưởng phòng</div>
                            <div style="font-weight:700; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $department->manager ?: '—' }}</div>
                        </div>
                        <div style="background:#f8fafc; border:1px solid var(--line); border-radius:10px; padding:8px 10px;">
                            <div style="color:var(--muted); font-size:12px;">Chức vụ</div>
                            <div style="font-weight:700;">{{ $department->positions_count ?? 0 }}</div>
                        </div>
                    </div>

                    <div style="flex:1; font-size:13.5px; color:var(--muted); line-height:1.5;">
                        {{ $department->description ?: 'Chưa có mô tả chức năng.' }}
                    </div>

                    <div class="actions" style="border-top:1px solid var(--line); padding-top:10px;">
                        <a class="btn link" href="{{ route('departments.show', $department) }}">Xem</a>
                        <a class="btn" href="{{ route('departments.edit', $department) }}">Sửa</a>
                        <a class="btn danger" href="{{ route('deletion_requests.create', ['kind' => 'department', 'target' => $department->id]) }}">Yêu cầu xóa</a>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="pagination">{{ $departments->links() }}</div>
    @else
        <div class="card"><div class="empty">Chưa có phòng ban.</div></div>
    @endif
@endsection