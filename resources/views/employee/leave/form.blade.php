@extends('layouts.app')

@section('title', 'Tạo đơn nghỉ phép')

@section('content')
    <div class="page-head">
        <div>
            <h1>Tạo đơn nghỉ phép</h1>
            <p class="muted">Điền thông tin chi tiết để gửi đơn nghỉ phép tới HR.</p>
        </div>
        <a class="btn link" href="{{ route('me.leave_requests') }}">Quay lại danh sách</a>
    </div>

    @if ($errors->any())
        <div class="alert">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('me.leave_requests.store') }}">
        @csrf

        <div class="field">
            <label>Ngày bắt đầu</label>
            <input type="date" name="start_date" value="{{ old('start_date') }}" required />
        </div>

        <div class="field">
            <label>Ngày kết thúc</label>
            <input type="date" name="end_date" value="{{ old('end_date') }}" required />
        </div>

        <div class="field">
            <label>Loại nghỉ phép</label>
            <select name="type" required>
                <option value="">-- Chọn loại nghỉ --</option>
                <option value="annual" {{ old('type') === 'annual' ? 'selected' : '' }}>Nghỉ hàng năm</option>
                <option value="sick" {{ old('type') === 'sick' ? 'selected' : '' }}>Nghỉ ốm</option>
                <option value="personal" {{ old('type') === 'personal' ? 'selected' : '' }}>Nghỉ việc riêng</option>
                <option value="unpaid" {{ old('type') === 'unpaid' ? 'selected' : '' }}>Không lương</option>
            </select>
        </div>

        <div class="field">
            <label>Lý do</label>
            <textarea name="reason">{{ old('reason') }}</textarea>
        </div>

        <button class="btn primary" type="submit">Gửi đơn nghỉ phép</button>
    </form>
@endsection
