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
        @if (empty($positions))
            <div class="empty">Không có chức vụ nào được lưu.</div>
        @else
            <div class="table-responsive">
                <table class="table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb;">STT</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb;">Tên chức vụ</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb;">Phòng ban</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb;">Mô tả</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb;">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($positions as $index => $position)
                            <tr>
                                <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">{{ $index + 1 }}</td>
                                <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">{{ $position['name'] }}</td>
                                <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">{{ $position['department'] }}</td>
                                <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">{{ $position['description'] }}</td>
                                <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">{{ $position['status'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
