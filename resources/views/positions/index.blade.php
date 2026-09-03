@extends('layouts.app')

@section('title', 'Chức vụ')

@section('content')
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
            <div class="table-responsive">
                <table class="table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb;">STT</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb;">Tên chức vụ</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb;">Phòng ban</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb;">Mô tả</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($positions as $index => $position)
                            <tr>
                                <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">{{ $index + 1 }}</td>
                                <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">
                                    <strong>{{ $position->name }}</strong>
                                    @if ($position->level)
                                        <br><span class="muted" style="font-size:12px;">Cấp bậc: {{ $position->level }}</span>
                                    @endif
                                </td>
                                <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">{{ optional($position->department)->name ?? '—' }}</td>
                                <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">{{ $position->description ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection