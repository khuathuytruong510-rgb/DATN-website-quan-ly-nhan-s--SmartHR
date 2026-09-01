@extends('layouts.app')

@section('title', 'Chức vụ')

@section('content')
    <div class="page-head">
        <div>
            <h1>Chức vụ</h1>
            <p class="muted">Danh sách chức vụ tiêu biểu theo phòng ban. Kích vào một phòng ban để xem chức vụ của phòng đó.</p>
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
            @foreach ($departments as $dept)
                @if ($positions->contains(fn ($p) => $p->department_id === $dept->id))
                    @include('positions._table', [
                        'positions' => $positions->where('department_id', $dept->id),
                        'title' => $dept->name,
                        'deptLink' => route('departments.show', $dept),
                    ])
                @endif
            @endforeach
        @endif
    </div>
@endsection