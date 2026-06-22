@extends('layouts.app')

@section('title', 'Chức vụ')

@section('content')
    <div class="page-head">
        <div>
            <h1>Chức vụ</h1>
            <p class="muted">Danh sách các chức vụ nhân viên hiện có trong hệ thống.</p>
        </div>
    </div>

    <div class="card">
        @if ($positions->isEmpty())
            <div class="empty">Không có chức vụ nào được lưu.</div>
        @else
            <ul style="margin: 0; padding-left: 16px;">
                @foreach ($positions as $position)
                    <li>{{ $position }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
