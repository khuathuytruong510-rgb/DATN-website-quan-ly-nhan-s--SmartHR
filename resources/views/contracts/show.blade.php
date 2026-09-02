@extends('layouts.app')

@section('content')
<div class="contract-page">
<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Chi tiết hợp đồng</h2>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('contracts.index') }}">Quay lại</a>
            @if(auth()->user()?->canManageHr())
                @unless($contract->isContentLocked())
                <a class="btn btn-outline-secondary" href="{{ route('contracts.edit', $contract) }}">Sửa</a>
                @endunless
                <a class="btn btn-outline-info" href="{{ route('contracts.renew', $contract) }}">Gia hạn</a>
            @endif
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    @if(auth()->user()?->is_hr && $contract->isAwaitingHrSend())
        <div class="alert alert-warning border-warning shadow-sm mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <strong>Bước tiếp theo — Gửi ký</strong>
                    <p class="mb-0 small">Hợp đồng đang ở trạng thái <strong>nháp</strong>. HR kiểm tra xong thì gửi ký để khóa tài liệu và chuyển cho Giám đốc ký trước, nhân viên ký sau.</p>
                </div>
                <form action="{{ route('contracts.send_for_signature', $contract) }}" method="POST" class="mb-0">
                    @csrf
                    <button class="btn btn-warning btn-lg px-4" type="submit">Gửi ký (Giám đốc → nhân viên)</button>
                </form>
            </div>
        </div>
    @elseif(auth()->user()?->canManageHr() && ! auth()->user()?->is_hr)
        <div class="alert alert-info mb-4">
            Chỉ tài khoản <strong>HR</strong> (ví dụ <code>hr@smarthr.com</code>) mới có quyền bấm <strong>Gửi ký</strong>.
        </div>
    @elseif(auth()->user()?->is_hr && ! $contract->isAwaitingHrSend() && ! $contract->isFullySigned())
        <div class="alert alert-secondary mb-4">
            Hợp đồng trạng thái <strong>{{ $contract->statusLabel() }}</strong> — không còn ở bước gửi ký.
            @if($contract->isPendingDirectorEsign())
                Chờ Giám đốc đăng nhập và ký phía doanh nghiệp.
            @elseif($contract->isPendingEmployeeEsign())
                Chờ nhân viên ký phía người lao động.
            @endif
        </div>
    @elseif(auth()->user()?->is_hr && $contract->isFullySigned())
        <div class="alert alert-success mb-4">
            Hợp đồng đã đủ chữ ký hai bên{{ $contract->status === 'active' ? ' và đang có hiệu lực' : '' }} — không cần gửi ký lại.
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white"><h5 class="mb-0">Thông tin hợp đồng</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><strong>Mã hợp đồng:</strong> {{ $contract->contract_code ?? '—' }}</div>
                        <div class="col-md-6"><strong>Loại hợp đồng:</strong> {{ $contract->contract_type ? ucfirst(str_replace('_', ' ', $contract->contract_type)) : '—' }}</div>
                        <div class="col-md-6"><strong>Ngày bắt đầu:</strong> {{ optional($contract->start_date)->format('d/m/Y') ?? '—' }}</div>
                        <div class="col-md-6"><strong>Ngày kết thúc:</strong> {{ optional($contract->end_date)->format('d/m/Y') ?? '—' }}</div>
                        <div class="col-md-6"><strong>Lương:</strong> {{ number_format($contract->salary ?? 0, 0, ',', '.') }} VNĐ</div>
                        <div class="col-md-6"><strong>Phụ cấp:</strong> {{ number_format($contract->allowance ?? 0, 0, ',', '.') }} VNĐ</div>
                        <div class="col-md-6"><strong>Nơi làm việc:</strong> {{ $contract->workplace ?? '—' }}</div>
                        <div class="col-md-6"><strong>Người tạo:</strong> {{ optional($contract->createdByUser)->name ?? '—' }}</div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white"><h5 class="mb-0">Thông tin nhân viên</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><strong>Nhân viên:</strong> {{ optional($contract->employee)->name ?? '—' }}</div>
                        <div class="col-md-6"><strong>Email:</strong> {{ optional($contract->employee)->email ?? '—' }}</div>
                        <div class="col-md-6"><strong>Chức vụ:</strong> {{ optional($contract->employee)->position ?? '—' }}</div>
                        <div class="col-md-6"><strong>Phòng ban:</strong> {{ optional(optional($contract->employee)->department)->name ?? '—' }}</div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white"><h5 class="mb-0">Ký kết / khóa tài liệu</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><strong>Giám đốc ký (doanh nghiệp):</strong> {{ $contract->director_signed_at ? optional($contract->director_signed_at)->format('d/m/Y H:i') : 'Chưa ký' }}</div>
                        <div class="col-md-6"><strong>Nhân viên ký (người lao động):</strong> {{ $contract->employee_signed_at ? optional($contract->employee_signed_at)->format('d/m/Y H:i') : 'Chưa ký' }}</div>
                        <div class="col-md-12"><strong>SHA-256:</strong> <code style="word-break:break-all;">{{ $contract->document_hash ?: 'Chưa khóa tài liệu' }}</code></div>
                        @if($contract->director_signed_at)
                        <div class="col-md-12">
                            Giám đốc:
                            @if($directorSignatureValid ?? false)
                                <span class="text-success fw-semibold">✓ Hợp lệ</span>
                            @else
                                <span class="text-danger fw-semibold">✗ Không khớp</span>
                            @endif
                            @if($contract->directorSignature?->provider_transaction_id)
                                · {{ $contract->directorSignature->provider_transaction_id }}
                            @endif
                        </div>
                        @endif
                        @if($contract->employee_signed_at)
                        <div class="col-md-12">
                            Nhân viên:
                            @if($employeeSignatureValid ?? false)
                                <span class="text-success fw-semibold">✓ Hợp lệ</span>
                            @else
                                <span class="text-danger fw-semibold">✗ Không khớp</span>
                            @endif
                            @if($contract->employeeSignature?->provider_transaction_id)
                                · {{ $contract->employeeSignature->provider_transaction_id }}
                            @endif
                        </div>
                        @endif
                        <div class="col-md-12">
                            <a href="{{ route('contracts.document', $contract) }}" target="_blank">Xem tài liệu / in PDF</a>
                        </div>
                        <div class="col-md-12"></div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white"><h5 class="mb-0">Điều khoản</h5></div>
                <div class="card-body">
                    <div class="border rounded p-3 bg-light" style="white-space:pre-wrap;">{{ $contract->contract_content ?? $contract->terms ?? '—' }}</div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white"><h5 class="mb-0">Lịch sử hợp đồng</h5></div>
                <div class="card-body">
                    @forelse($contract->logs->sortByDesc('created_at') as $log)
                        <div class="border-start ps-3 pb-3">
                            <div class="fw-semibold">{{ $log->message }}</div>
                            <div class="text-muted small">{{ optional($log->created_at)->format('d/m/Y H:i') }}
                                @if($log->user) · {{ $log->user->name }}@endif
                            </div>
                        </div>
                    @empty
                        <p class="mb-0">Chưa có nhật ký.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white"><h5 class="mb-0">Trạng thái</h5></div>
                <div class="card-body">
                    <div class="mb-3"><span class="badge bg-{{ $contract->isFullySigned() ? 'success' : 'warning' }}">{{ $contract->statusLabel() }}</span></div>
                    @if($contract->isContentLocked())
                        <p class="text-muted small">Tài liệu đã khóa. Không sửa nội dung bản này. Nếu cần thay đổi, tạo hợp đồng/gia hạn mới rồi ký lại.</p>
                    @endif

                    @if(auth()->user()?->is_hr && $contract->isAwaitingHrSend())
                    <form action="{{ route('contracts.send_for_signature', $contract) }}" method="POST" class="mb-2">
                        @csrf
                        <button class="btn btn-warning w-100" type="submit">Gửi ký (Giám đốc → nhân viên)</button>
                    </form>
                    @endif

                    @if(auth()->user()?->is_director && $contract->isPendingDirectorEsign())
                    <form action="{{ route('contracts.sign', $contract) }}" method="POST" class="mb-2"
                          onsubmit="return confirm('Xác nhận ký hợp đồng {{ $contract->contract_code }} phía doanh nghiệp?\nHash: {{ $contract->document_hash ?: '(sẽ khóa khi ký)' }}\nĐây là mô phỏng, chưa phải chứng thư số pháp lý.\nSau khi ký, nhân viên mới được ký.');">
                        @csrf
                        <button class="btn btn-primary w-100" type="submit">Ký phía doanh nghiệp</button>
                    </form>
                    <form action="{{ route('contracts.reject_signature', $contract) }}" method="POST" class="mb-2">
                        @csrf
                        <input class="form-control mb-2" name="reason" required minlength="8" placeholder="Lý do từ chối">
                        <button class="btn btn-outline-danger w-100" type="submit">Từ chối</button>
                    </form>
                    @endif

                    @if($contract->needsExpiryHandling() && auth()->user()?->canManageHr())
                    <button type="button" class="btn btn-warning w-100 mb-2" onclick="document.getElementById('handle-contract-{{ $contract->id }}').showModal()">Xử lý hợp đồng</button>
                    <x-contract_handle_dialog :contract="$contract" />
                    @endif
                    @if(auth()->user()?->canManageHr())
                    <a href="{{ route('contracts.renew', $contract) }}" class="btn btn-outline-success w-100 mb-2">Gia hạn hợp đồng</a>
                    @unless($contract->isContentLocked())
                        @if(isset($payroll) && $payroll)
                        <form action="{{ route('contracts.sync_salary', $contract) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-warning w-100" onclick="return confirm('Đồng bộ lương hợp đồng theo bảng lương gần nhất?')">Đồng bộ lương</button>
                        </form>
                        @endif
                    @endunless
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
