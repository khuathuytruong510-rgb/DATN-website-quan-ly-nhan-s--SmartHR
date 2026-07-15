@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">{{ $isEdit ? 'Chỉnh sửa hợp đồng' : 'Tạo hợp đồng' }}</h2>
            <p class="text-muted mb-0">Quản lý hợp đồng nhân viên theo chuẩn SmartHR.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('contracts.index') }}">Quay lại</a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ $isEdit ? route('contracts.update', $contract) : route('contracts.store') }}" enctype="multipart/form-data">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">1. Thông tin nhân viên</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nhân viên <span class="text-danger">*</span></label>
                                <select name="employee_id" id="employeeSelect" class="form-select" required>
                                    <option value="">-- Chọn nhân viên --</option>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}" {{ old('employee_id', $contract->employee_id) == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                                    @endforeach
                                </select>
                                @error('employee_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mã nhân viên</label>
                                <input type="text" id="employeeCode" class="form-control" readonly value="{{ optional($contract->employee)->employee_code ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Họ tên</label>
                                <input type="text" id="employeeName" class="form-control" readonly value="{{ optional($contract->employee)->name ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phòng ban</label>
                                <input type="text" id="employeeDepartment" class="form-control" readonly value="{{ optional(optional($contract->employee)->department)->name ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Chức vụ</label>
                                <input type="text" id="employeePosition" class="form-control" readonly value="{{ optional($contract->employee)->position ?? '' }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">2. Thông tin hợp đồng</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Mã hợp đồng</label>
                                <input type="text" name="contract_code" class="form-control" value="{{ old('contract_code', $contract->contract_code ?? '') }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tên hợp đồng <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="{{ old('title', $contract->title) }}" required>
                                @error('title')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Loại hợp đồng <span class="text-danger">*</span></label>
                                <select name="contract_type" class="form-select" required>
                                    <option value="">-- Chọn loại hợp đồng --</option>
                                    <option value="probation" {{ old('contract_type', $contract->contract_type) == 'probation' ? 'selected' : '' }}>Thử việc</option>
                                    <option value="fixed_term" {{ old('contract_type', $contract->contract_type) == 'fixed_term' ? 'selected' : '' }}>Xác định thời hạn</option>
                                    <option value="indefinite" {{ old('contract_type', $contract->contract_type) == 'indefinite' ? 'selected' : '' }}>Không xác định thời hạn</option>
                                    <option value="consultant" {{ old('contract_type', $contract->contract_type) == 'consultant' ? 'selected' : '' }}>Cộng tác viên</option>
                                </select>
                                @error('contract_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ngày ký</label>
                                <input type="date" name="sign_date" class="form-control" value="{{ old('sign_date', optional($contract->sign_date)->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ngày bắt đầu <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control" value="{{ old('start_date', optional($contract->start_date)->format('Y-m-d')) }}" required>
                                @error('start_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ngày kết thúc</label>
                                <input type="date" name="end_date" class="form-control" value="{{ old('end_date', optional($contract->end_date)->format('Y-m-d')) }}">
                                @error('end_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Trạng thái</label>
                                <select name="status" class="form-select">
                                    <option value="pending" {{ old('status', $contract->status) == 'pending' ? 'selected' : '' }}>Chờ hiệu lực</option>
                                    <option value="active" {{ old('status', $contract->status) == 'active' ? 'selected' : '' }}>Có hiệu lực</option>
                                    <option value="expired" {{ old('status', $contract->status) == 'expired' ? 'selected' : '' }}>Hết hạn</option>
                                    <option value="canceled" {{ old('status', $contract->status) == 'canceled' ? 'selected' : '' }}>Đã hủy</option>
                                </select>
                                @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">3. Thông tin lương</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Lương cơ bản <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="base_salary" class="form-control" value="{{ old('base_salary', $contract->base_salary ?? 0) }}" required>
                                @error('base_salary')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phụ cấp</label>
                                <input type="number" step="0.01" min="0" name="allowance" class="form-control" value="{{ old('allowance', $contract->allowance ?? 0) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Thưởng</label>
                                <input type="number" step="0.01" min="0" name="bonus" class="form-control" value="{{ old('bonus', $contract->bonus ?? 0) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hình thức thanh toán</label>
                                <select name="payment_method" class="form-select">
                                    <option value="">-- Chọn hình thức --</option>
                                    <option value="bank_transfer" {{ old('payment_method', $contract->payment_method) == 'bank_transfer' ? 'selected' : '' }}>Chuyển khoản</option>
                                    <option value="cash" {{ old('payment_method', $contract->payment_method) == 'cash' ? 'selected' : '' }}>Tiền mặt</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">4. Điều khoản</h5>
                    </div>
                    <div class="card-body">
                        <textarea name="terms" class="form-control" rows="8">{{ old('terms', $contract->terms) }}</textarea>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">5. File hợp đồng</h5>
                    </div>
                    <div class="card-body">
                        <input type="file" name="document" class="form-control" accept=".pdf,.doc,.docx">
                        @error('document')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        @if($contract->document_name)
                            <div class="mt-2">
                                <span class="text-muted">File hiện tại:</span> <strong>{{ $contract->document_name }}</strong>
                                @if($contract->document_path)
                                    <a class="btn btn-sm btn-outline-primary ms-2" href="{{ Storage::url($contract->document_path) }}" target="_blank">Xem</a>
                                    <a class="btn btn-sm btn-outline-secondary ms-1" href="{{ Storage::url($contract->document_path) }}" download>Download</a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">6. Ghi chú</h5>
                    </div>
                    <div class="card-body">
                        <textarea name="notes" class="form-control" rows="4">{{ old('notes', $contract->notes) }}</textarea>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Lưu</button>
                    <button type="submit" name="send_email" value="1" class="btn btn-outline-primary">Lưu &amp; Gửi Email</button>
                    <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary">Hủy</a>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top: 1rem;">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Thông tin nhanh</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="fw-semibold">Trạng thái hợp đồng</div>
                            <span class="badge bg-{{ $contract->status === 'active' ? 'success' : ($contract->status === 'expired' ? 'danger' : ($contract->status === 'canceled' ? 'secondary' : 'warning')) }}">
                                {{ $contract->status === 'active' ? 'Có hiệu lực' : ($contract->status === 'expired' ? 'Hết hạn' : ($contract->status === 'canceled' ? 'Đã hủy' : 'Chờ hiệu lực')) }}
                            </span>
                        </div>
                        <div class="mb-3">
                            <div class="fw-semibold">Ngày còn hiệu lực</div>
                            <div>{{ optional($contract->end_date)->format('d/m/Y') ?? 'Không giới hạn' }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="fw-semibold">Ngày hết hạn</div>
                            <div>{{ optional($contract->end_date)->format('d/m/Y') ?? 'Không giới hạn' }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Người ký</label>
                            <select name="signer_id" class="form-select">
                                <option value="">-- Chọn người ký --</option>
                                @foreach($signers as $signer)
                                    <option value="{{ $signer->id }}" {{ old('signer_id', $contract->signer_id) == $signer->id ? 'selected' : '' }}>{{ $signer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.getElementById('employeeSelect')?.addEventListener('change', function () {
        const employeeId = this.value;
        if (!employeeId) {
            document.getElementById('employeeCode').value = '';
            document.getElementById('employeeName').value = '';
            document.getElementById('employeeDepartment').value = '';
            document.getElementById('employeePosition').value = '';
            return;
        }

        fetch(`/employees/${employeeId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('employeeCode').value = data.employee_code || '';
                document.getElementById('employeeName').value = data.name || '';
                document.getElementById('employeeDepartment').value = data.department?.name || '';
                document.getElementById('employeePosition').value = data.position || '';
            });
    });
</script>
@endsection
