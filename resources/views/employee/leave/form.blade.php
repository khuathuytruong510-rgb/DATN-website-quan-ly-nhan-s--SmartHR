@extends('layouts.app')

@section('title', 'Tạo đơn nghỉ phép')

@php $approver = \App\Support\RequestApprover::queueLabel($employee); @endphp
@section('content')
@section('breadcrumb')
<li><a href="{{ route('me.dashboard') }}">Dashboard</a></li>
<li><a href="{{ route('me.leave_requests') }}">Đơn xin nghỉ</a></li>
<li>Tạo đơn</li>
@endsection
    <div class="page-head">
        <div>
            <h1>Tạo đơn nghỉ phép</h1>
        </div>
        <a class="btn link" href="{{ route('me.leave_requests') }}">Quay lại danh sách</a>
    </div>

    @if(session('error'))
        <div class="alert error">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert error">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(empty($contract))
        <div class="callout warn">
            <p class="callout-title">Chưa có hợp đồng hiệu lực</p>
            <p>Không gửi được đơn nghỉ phép. Liên hệ HR để ký / kích hoạt hợp đồng.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('me.leave_requests.store') }}" onsubmit="return confirm('Xác nhận gửi đơn? Hệ thống sẽ kiểm tra điều kiện nghỉ phép trên hợp đồng và luật lao động. Nếu đủ điều kiện, đơn được gửi {{ $approver }} duyệt.');">
        @csrf

        <div class="field">
            <label class="form-label" for="leave-type">Loại nghỉ phép</label>
            @include('components.leave_type_select', ['leaveTypes' => $leaveTypes ?? null, 'selected' => $defaultType ?? null])
        </div>

        @include('components.leave_quota_card', [
            'guides' => $leaveLimit['types'] ?? [],
            'initialType' => old('type', $defaultType ?? 'annual'),
        ])

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <div class="field">
                    <label class="form-label">Ngày bắt đầu</label>
                    <input class="form-control" id="leave-start" type="date" name="start_date" value="{{ old('start_date') }}" required />
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="field">
                    <label class="form-label">Ngày kết thúc</label>
                    <input class="form-control" id="leave-end" type="date" name="end_date" value="{{ old('end_date') }}" required />
                </div>
            </div>
        </div>

        <div class="field">
            <label class="check-row">
                <input type="checkbox" name="half_day" value="1" {{ old('half_day') ? 'checked' : '' }} id="half_day" />
                Nghỉ 1/2 ngày
            </label>
        </div>

        <div class="field">
            <label class="form-label">Lý do</label>
            <textarea class="form-control" name="reason">{{ old('reason') }}</textarea>
        </div>

        <div class="callout warn">
            <label class="check-row">
                <input type="checkbox" name="is_urgent" value="1" {{ old('is_urgent') ? 'checked' : '' }} id="is_urgent" />
                Đánh dấu khẩn cấp ({{ $approver }} ưu tiên xem)
            </label>
            <div id="urgent_reason_wrapper" style="margin-top: 12px; display: {{ old('is_urgent') ? 'block' : 'none' }};">
                <label class="form-label" for="urgent_reason">Lý do khẩn cấp</label>
                <textarea class="form-control" name="urgent_reason" id="urgent_reason" rows="3" placeholder="Mô tả lý do cần duyệt gấp...">{{ old('urgent_reason') }}</textarea>
            </div>
        </div>

        <button class="btn primary" type="submit" @if(empty($contract)) disabled @endif>Xác nhận gửi</button>
    </form>
@endsection

@push('scripts')
<script>
document.getElementById('is_urgent')?.addEventListener('change', function() {
    const wrapper = document.getElementById('urgent_reason_wrapper');
    const textarea = document.getElementById('urgent_reason');
    if (!wrapper || !textarea) return;
    wrapper.style.display = this.checked ? 'block' : 'none';
    textarea.required = this.checked;
    if (!this.checked) textarea.value = '';
});
window.SmartHrLeaveQuota?.bind({
    typeSelect: document.getElementById('leave-type'),
    startInput: document.getElementById('leave-start'),
    endInput: document.getElementById('leave-end'),
    halfDay: document.getElementById('half_day'),
});
</script>
@endpush
