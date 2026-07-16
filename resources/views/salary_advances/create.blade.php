@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Yêu cầu ứng lương</h1>

    <form method="POST" action="{{ route('me.salary_advances.store') }}">
        @csrf
        <div class="mb-3">
            <label>Số tiền</label>
            <input type="number" step="0.01" name="amount" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Lý do</label>
            <textarea name="reason" class="form-control"></textarea>
        </div>
        <div class="mb-3">
            <label>Ngày yêu cầu</label>
            <input type="date" name="requested_at" class="form-control" required value="{{ date('Y-m-d') }}">
        </div>
        <button class="btn btn-primary">Gửi yêu cầu</button>
    </form>
</div>
@endsection
