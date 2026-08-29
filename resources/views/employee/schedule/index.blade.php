@extends('layouts.app')

@section('title', 'Lịch làm việc')

@section('breadcrumb')
<li><a href="{{ route('me.dashboard') }}">Dashboard</a></li>
<li>Lịch làm việc</li>
@endsection

@section('content')
    <div class="page-head">
        <div>
            <h1>Lịch làm việc</h1>
            <p class="muted">Xem lịch làm việc theo tuần và tháng.</p>
        </div>
    </div>

    <div class="card">
        <div class="empty">Hiện chưa có lịch làm việc được phân công.</div>
    </div>
@endsection
