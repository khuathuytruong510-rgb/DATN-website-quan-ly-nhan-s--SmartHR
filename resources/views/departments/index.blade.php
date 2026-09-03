@extends('layouts.app')

@section('title', 'Phòng ban - SmartHR')

@section('content')
    <div class="dept-page">
        <div class="page-head">
            <div>
                <h1>Phòng ban</h1>
            </div>
            @if(auth()->user()?->canManageHr())
            <div class="page-actions">
                <a class="btn" href="{{ route('transfers.create') }}">Điều chuyển NV</a>
                <a class="btn primary" href="{{ route('departments.create') }}">Thêm phòng ban</a>
            </div>
            @endif
        </div>

        @if ($departments->isEmpty())
            <div class="empty">Chưa có phòng ban.</div>
        @else
            <div class="dept-grid">
                @foreach ($departments as $department)
                    @php $pendingDeletionId = ($pendingDepartmentDeletions ?? collect())[$department->id] ?? null; @endphp
                    <article class="dept-card">
                        <div class="dept-card-top">
                            <h2 class="dept-card-title" title="{{ $department->name }}">{{ $department->name }}</h2>
                            <div class="dept-card-count" title="{{ $department->employees_count }} nhân viên">
                                <strong>{{ $department->employees_count }}</strong>
                                <span>nhân viên</span>
                            </div>
                        </div>

                        <span class="dept-code">{{ $department->code }}</span>

                        <div class="dept-meta">
                            <div class="dept-meta-box">
                                <div class="dept-meta-label">Trưởng phòng</div>
                                <div class="dept-meta-value" title="{{ $department->manager ?: '—' }}">{{ $department->manager ?: '—' }}</div>
                            </div>
                            <div class="dept-meta-box">
                                <div class="dept-meta-label">Chức vụ</div>
                                <div class="dept-meta-value">{{ $department->positions_count }}</div>
                            </div>
                        </div>

                        <p class="dept-card-desc">{{ $department->description ?: '—' }}</p>

                        <div class="dept-card-actions">
                            <a class="dept-act dept-act-view" href="{{ route('departments.show', $department) }}">Xem</a>
                            @if(auth()->user()?->canManageHr())
                                <a class="dept-act dept-act-edit" href="{{ route('departments.edit', $department) }}">Sửa</a>
                                @if($pendingDeletionId)
                                    <a class="dept-act dept-act-pending" href="{{ route('deletion_requests.show', $pendingDeletionId) }}">Chờ GĐ duyệt</a>
                                @elseif($department->isBoard())
                                    <span class="dept-act dept-act-locked">Không xóa</span>
                                @else
                                    <a class="dept-act dept-act-delete" href="{{ route('deletion_requests.create_department', $department) }}">Yêu cầu xóa</a>
                                @endif
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
            @if($departments->hasPages())
                <div class="pagination">{{ $departments->links() }}</div>
            @endif
        @endif
    </div>

    <style>
        .dept-page { display: grid; gap: 22px; }
        .dept-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            align-items: stretch;
        }
        .dept-card {
            display: flex;
            flex-direction: column;
            min-width: 0;
            background: #fff;
            border: 1px solid #e8edf5;
            border-radius: 16px;
            padding: 18px 18px 14px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 8px 24px rgba(15, 23, 42, .05);
        }
        .dept-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }
        .dept-card-title {
            margin: 0;
            min-width: 0;
            font-size: 15.5px;
            font-weight: 800;
            letter-spacing: -.02em;
            line-height: 1.35;
            color: #0f172a;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .dept-card-count {
            flex-shrink: 0;
            text-align: right;
            line-height: 1.15;
        }
        .dept-card-count strong {
            display: block;
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -.03em;
        }
        .dept-card-count span {
            display: block;
            margin-top: 2px;
            font-size: 11.5px;
            font-weight: 600;
            color: #94a3b8;
        }
        .dept-code {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            align-self: flex-start;
            min-width: 34px;
            height: 26px;
            padding: 0 10px;
            margin-bottom: 12px;
            border-radius: 999px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #475569;
            font-size: 12px;
            font-weight: 750;
            letter-spacing: .02em;
        }
        .dept-meta {
            display: grid;
            grid-template-columns: 1.4fr .8fr;
            gap: 8px;
            margin-bottom: 12px;
        }
        .dept-meta-box {
            min-width: 0;
            border: 1px solid #e8edf5;
            border-radius: 12px;
            padding: 10px 12px;
            background: #f8fafc;
        }
        .dept-meta-label {
            color: #94a3b8;
            font-size: 11.5px;
            font-weight: 650;
            margin-bottom: 4px;
        }
        .dept-meta-value {
            font-size: 13.5px;
            font-weight: 750;
            color: #0f172a;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .dept-card-desc {
            margin: 0 0 14px;
            color: #64748b;
            font-size: 13px;
            line-height: 1.55;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 2.5em;
            flex: 1 1 auto;
        }
        .dept-card-actions {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-top: auto;
            padding-top: 2px;
        }
        .dept-act {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            padding: 6px 8px;
            border-radius: 10px;
            border: 1px solid transparent;
            font-size: 13px;
            font-weight: 750;
            line-height: 1.2;
            text-decoration: none;
            white-space: nowrap;
            transition: background .15s ease, border-color .15s ease, color .15s ease;
        }
        .dept-act-view {
            color: #2563eb;
            background: #eff6ff;
            border-color: #bfdbfe;
        }
        .dept-act-view:hover { background: #dbeafe; color: #1d4ed8; text-decoration: none; }
        .dept-act-edit {
            color: #0f172a;
            background: #fff;
            border-color: #cbd5e1;
        }
        .dept-act-edit:hover { background: #f8fafc; border-color: #94a3b8; color: #0f172a; text-decoration: none; }
        .dept-act-delete {
            color: #b91c1c;
            background: #fef2f2;
            border-color: #fecaca;
        }
        .dept-act-delete:hover { background: #fee2e2; color: #991b1b; text-decoration: none; }
        .dept-act-pending {
            color: #9a3412;
            background: #fffbeb;
            border-color: #fde68a;
        }
        .dept-act-pending:hover { background: #fef3c7; color: #92400e; text-decoration: none; }
        .dept-act-locked {
            color: #94a3b8;
            background: #f8fafc;
            border-color: #e2e8f0;
            cursor: default;
        }
        @media (max-width: 1280px) {
            .dept-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 1024px) {
            .dept-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 700px) {
            .dept-grid { grid-template-columns: 1fr; }
            .dept-card-actions { grid-template-columns: 1fr; }
        }
    </style>
@endsection
