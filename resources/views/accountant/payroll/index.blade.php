@extends('layouts.app')

@section('title', 'Quản lý bảng lương')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('accountant.dashboard') }}">Kế toán</a></li>
<li>Quản lý bảng lương</li>
@endsection

<div class="page-head">
    <div>
        <h1><i class="bi bi-cash-stack me-2"></i>Quản lý bảng lương</h1>
        <p class="muted">Danh sách bảng lương</p>
    </div>
    <div class="actions">
        <a class="btn primary" href="{{ route('accountant.payroll.generate') }}"><i class="bi bi-calculator me-1"></i> Tính lương</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end mb-3">
            <div class="col-md-5">
                <input name="q" placeholder="Tìm theo tên/ email/ tháng" value="{{ request('q') }}" class="form-control">
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending" {{ request('status')=='pending' ? 'selected' : '' }}>Chờ duyệt</option>
                    <option value="approved" {{ request('status')=='approved' ? 'selected' : '' }}>Đã duyệt</option>
                    <option value="paid" {{ request('status')=='paid' ? 'selected' : '' }}>Đã trả</option>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn primary w-100" type="submit"><i class="bi bi-search me-1"></i> Tìm</button>
            </div>
        </form>

        @if($payrolls->count() === 0)
            <div class="empty text-center py-4">
                <i class="bi bi-inbox" style="font-size:48px;color:#cbd5e1;"></i>
                <p class="mt-2">Chưa có bảng lương. Hãy tạo bảng lương mới.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tháng</th>
                            <th>Mã NV</th>
                            <th>Nhân viên</th>
                            <th>Tổng</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payrolls as $p)
                            <tr>
                                <td>{{ $p->month }}</td>
                                <td>
                                    {{ optional($p->employee)->name }}
                                    <br><small class="text-muted">{{ optional($p->employee)->email }}</small>
                                </td>
                                <td><strong>{{ number_format($p->total_salary ?? 0, 0, '.', ',') }} VNĐ</strong></td>
                                <td>
                                    @if($p->status === 'pending')<span class="badge bg-warning text-dark">Chờ duyệt</span>
                                    @elseif($p->status === 'approved')<span class="badge bg-primary">Đã duyệt</span>
                                    @elseif($p->status === 'paid')<span class="badge bg-success">Đã trả</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2 justify-content-end flex-wrap">
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('accountant.payroll.show', $p) }}">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-secondary" type="button" onclick="confirmRecalculate({{ $p->id }})">
                                            <i class="bi bi-arrow-clockwise"></i> Tính lại
                                        </button>
                                        @if(!$p->locked)
                                            <button class="btn btn-sm btn-outline-danger" type="button" onclick="confirmLock({{ $p->id }})">
                                                <i class="bi bi-lock"></i> Khoá
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-outline-success" type="button" onclick="confirmUnlock({{ $p->id }})">
                                                <i class="bi bi-unlock"></i> Mở khoá
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pagination">{{ $payrolls->links() }}</div>
        @endif
    </div>
</div>

<div class="modal fade" id="actionModal" tabindex="-1" aria-labelledby="actionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="actionModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="actionModalBody" class="mb-0"></p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn" data-bs-dismiss="modal">Hủy</button>
                <form id="actionForm" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn" id="actionBtn">Xác nhận</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function showModal(title, body, actionUrl, btnClass, btnText) {
    document.getElementById('actionModalLabel').innerHTML = title;
    document.getElementById('actionModalBody').innerHTML = body;
    document.getElementById('actionForm').action = actionUrl;
    var btn = document.getElementById('actionBtn');
    btn.className = 'btn ' + btnClass;
    btn.innerHTML = btnText;
    var modal = new bootstrap.Modal(document.getElementById('actionModal'));
    modal.show();
}

function confirmRecalculate(id) {
    showModal(
        '<i class="bi bi-arrow-clockwise text-primary me-2"></i>Tính lại lương',
        'Bạn có chắc chắn muốn tính lại bảng lương này?',
        '/accountant/payroll/' + id + '/recalculate',
        'btn-primary',
        '<i class="bi bi-arrow-clockwise me-1"></i> Tính lại'
    );
}

function confirmLock(id) {
    showModal(
        '<i class="bi bi-lock text-danger me-2"></i>Khoá bảng lương',
        'Bạn có chắc chắn muốn khoá bảng lương này? Sau khi khoá sẽ không thể chỉnh sửa.',
        '/accountant/payroll/' + id + '/lock',
        'btn-danger',
        '<i class="bi bi-lock me-1"></i> Khoá'
    );
}

function confirmUnlock(id) {
    showModal(
        '<i class="bi bi-unlock text-success me-2"></i>Mở khoá bảng lương',
        'Bạn có chắc chắn muốn mở khoá bảng lương này?',
        '/accountant/payroll/' + id + '/unlock',
        'btn-success',
        '<i class="bi bi-unlock me-1"></i> Mở khoá'
    );
}
</script>
@endpush
