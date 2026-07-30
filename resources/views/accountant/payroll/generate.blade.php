@extends('layouts.app')

@section('title', 'Tính lương')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('accountant.dashboard') }}">Kế toán</a></li>
<li>Tính lương</li>
@endsection

<div class="page-head">
    <div>
        <h1><i class="bi bi-calculator me-2"></i>Tính lương tự động</h1>
        <p class="muted">Tạo bảng lương cho toàn bộ nhân viên đang hoạt động cho tháng được chọn.</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('accountant.payroll.index') }}"><i class="bi bi-arrow-left me-1"></i> Quay lại danh sách</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('accountant.payroll.generate_post') }}" id="generateForm">
            @csrf
            <div class="field" style="max-width:400px;">
                <label for="month" class="form-label fw-bold">Chọn tháng</label>
                <input id="month" name="month" type="month" value="{{ now()->format('Y-m') }}" required class="form-control">
            </div>
            <button class="btn btn-primary" type="button" onclick="confirmGenerate()">
                <i class="bi bi-calculator me-1"></i> Tính lương tự động
            </button>
        </form>
    </div>
</div>

<div class="modal fade" id="generateModal" tabindex="-1" aria-labelledby="generateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="generateModalLabel">
                    <i class="bi bi-calculator text-primary me-2"></i>Tính lương tự động
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn tính lương cho tất cả nhân viên?</p>
                <p class="text-muted mb-0" style="font-size:13px;">Hệ thống sẽ tự động tính toán lương dựa trên dữ liệu chấm công và các khoản phụ cấp, khấu trừ.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('generateForm').submit();">
                    <i class="bi bi-calculator me-1"></i> Xác nhận tính lương
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function confirmGenerate() {
    var modal = new bootstrap.Modal(document.getElementById('generateModal'));
    modal.show();
}
</script>
@endpush
