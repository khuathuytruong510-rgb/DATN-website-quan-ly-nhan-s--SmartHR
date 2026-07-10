@extends('layouts.app')

@section('title', 'Tạo thông báo')

@section('content')
    <div class="page-head">
        <div>
            <h1>Tạo thông báo</h1>
            <p class="muted">Gửi thông báo đến nhân viên hoặc toàn bộ nhân viên.</p>
        </div>
    </div>

    <div class="card">
        <form action="{{ route('notifications.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="title" class="form-label">Tiêu đề</label>
                <input type="text" name="title" id="title" class="form-control" value="{{ old('title') }}" required>
            </div>

            <div class="mb-3">
                <label for="message" class="form-label">Nội dung</label>
                <textarea name="message" id="message" class="form-control" rows="5" required>{{ old('message') }}</textarea>
            </div>

            <div class="mb-3">
                <label for="target" class="form-label">Đối tượng</label>
                <select name="target" id="target" class="form-control" required>
                    @if ($user->is_admin)
                        <option value="employee">Nhân viên</option>
                        <option value="hr">HR</option>
                        <option value="all">Tất cả nhân viên (kể cả HR)</option>
                    @elseif ($user->is_hr)
                        <option value="employee">Nhân viên</option>
                    @endif
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Gửi thông báo</button>
        </form>
    </div>
@endsection
