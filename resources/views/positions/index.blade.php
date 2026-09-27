@extends('layouts.app')

@section('title', 'Chức vụ')

@section('content')
    <style>
        .position-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
        }
        .position-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 18px;
            background: #fff;
            box-shadow: 0 4px 14px rgba(15, 23, 42, .05);
        }
        .position-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }
        .position-card-index {
            display: inline-grid;
            place-items: center;
            width: 30px;
            height: 30px;
            border-radius: 9px;
            background: #eff6ff;
            color: #2563eb;
            font-weight: 800;
            flex: 0 0 auto;
        }
        .position-card-title { margin: 0; font-size: 17px; }
        .position-card-level { margin: 4px 0 0; color: #64748b; font-size: 13px; }
        .position-card-field { margin-top: 12px; }
        .position-card-label { display: block; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .position-card-value { margin-top: 3px; line-height: 1.5; }
        @media (max-width: 640px) {
            .position-cards { grid-template-columns: 1fr; }
        }
    </style>
    <div class="page-head">
        <div>
            <h1>Chức vụ</h1>
        </div>
    </div>

    <div class="card">
        <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:18px;">
            <a class="btn {{ $selected ? '' : 'primary' }}" href="{{ route('positions.index') }}">Tất cả</a>
            @foreach ($departments as $dept)
                <a class="btn {{ $selected && $selected->code === $dept->code ? 'primary' : '' }}"
                   href="{{ route('positions.index', ['department' => $dept->code]) }}">
                    {{ $dept->name }} ({{ $dept->positions_count }})
                </a>
            @endforeach
        </div>

        @if ($positions->isEmpty())
            <div class="empty">Không có chức vụ nào.</div>
        @elseif ($selected)
            @include('positions._table', [
                'positions' => $positions,
                'title' => $selected->name,
                'deptLink' => route('departments.show', $selected),
            ])
        @else
            <div class="position-cards">
                @foreach ($positions as $index => $position)
                    <article class="position-card">
                        <div class="position-card-head">
                            <div style="display:flex;gap:10px;align-items:flex-start;">
                                <span class="position-card-index">{{ $index + 1 }}</span>
                                <div>
                                    <h2 class="position-card-title">{{ $position->name }}</h2>
                                    @if ($position->level)
                                        <p class="position-card-level">Cấp bậc: {{ $position->level }}</p>
                                    @endif
                                </div>
                            </div>
                            <i class="bi bi-briefcase" style="color:#2563eb;font-size:20px;"></i>
                        </div>
                        <div class="position-card-field">
                            <span class="position-card-label">Phòng ban</span>
                            <div class="position-card-value">{{ optional($position->department)->name ?? '—' }}</div>
                        </div>
                        <div class="position-card-field">
                            <span class="position-card-label">Mô tả</span>
                            <div class="position-card-value">{{ $position->description ?: '-' }}</div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
@endsection