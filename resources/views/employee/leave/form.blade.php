@extends('layouts.app')

@section('title', 'Tạo đơn nghỉ phép')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('me.dashboard') }}">Dashboard</a></li>
<li><a href="{{ route('me.leave_requests') }}">Đơn xin nghỉ</a></li>
<li>Tạo đơn</li>
@endsection
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

    <div class="alert alert-info" style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #e8f4fd; border-left: 4px solid #2196F3; border-radius: 4px;">
        <strong>Quy định nghỉ phép:</strong> Mỗi nhân viên được phép nghỉ tối đa <strong>2 ngày/tháng</strong>.
        Nếu cần nghỉ nhiều hơn, vui lòng đánh dấu là <strong>khẩn cấp</strong> và cung cấp lý do thuyết phục để bộ phận hỗ trợ xem xét.
    </div>

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
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="half_day" value="1" {{ old('half_day') ? 'checked' : '' }} id="half_day" />
                <strong>Nghỉ 1/2 ngày</strong>
            </label>
            <p class="muted" style="margin: 0.25rem 0 0 1.5rem; font-size: 0.85em;">
                Chỉ áp dụng khi ngày bắt đầu = ngày kết thúc.
            </p>
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

        <div class="field" style="border: 1px solid #ddd; padding: 1rem; border-radius: 4px; background: #fff8e1;">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="is_urgent" value="1" {{ old('is_urgent') ? 'checked' : '' }} id="is_urgent" />
                <strong>Nghỉ phép khẩn cấp (vượt quá 2 ngày/tháng)</strong>
            </label>
            <p class="muted" style="margin: 0.5rem 0 0 1.5rem; font-size: 0.9em;">
                Chỉ chọn khi thực sự cần thiết. Cần cung cấp lý do thuyết phục để bộ phận hỗ trợ xem xét.
            </p>
            <div id="urgent_reason_wrapper" style="margin-top: 0.75rem; display: {{ old('is_urgent') ? 'block' : 'none' }};">
                <label for="urgent_reason">Lý do khẩn cấp <span style="color: red;">*</span></label>
                <textarea name="urgent_reason" id="urgent_reason" rows="3" placeholder="Mô tả chi tiết lý do tại sao bạn cần nghỉ phép vượt quá quy định...">{{ old('urgent_reason') }}</textarea>
            </div>
        </div>

        <button class="btn primary" type="submit" style="margin-top: 1rem;">Gửi đơn nghỉ phép</button>
    </form>

    <script>
        document.getElementById('is_urgent').addEventListener('change', function() {
            const wrapper = document.getElementById('urgent_reason_wrapper');
            const textarea = document.getElementById('urgent_reason');
            if (this.checked) {
                wrapper.style.display = 'block';
                textarea.required = true;
            } else {
                wrapper.style.display = 'none';
                textarea.required = false;
                textarea.value = '';
            }
        });
    </script>
@endsection
