@extends('layouts.employee')

@section('title', 'Bảng lương của tôi')

@section('breadcrumb')
<li><a href="{{ route('me.dashboard') }}">Dashboard</a></li>
<li>Bảng lương</li>
@endsection

@section('content')
    <div class="page-head">
        <div>
            <h1>Bảng lương của tôi</h1>
            <p class="muted">Xem và xác nhận phiếu lương</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert" style="background:#fee2e2;color:#dc2626;border-radius:8px;padding:12px;">{{ session('error') }}</div>
    @endif

    @if($payrolls->isEmpty())
        <div class="empty">Chưa có phiếu lương nào. Vui lòng liên hệ phòng nhân sự.</div>
    @else
        <div class="grid two-cols">
            <div>
                @foreach($payrolls as $p)
                    <div class="card" style="margin-bottom:18px; display:flex; gap:16px; align-items:stretch;">
                        <div style="flex:1;">
                            <div style="display:flex; gap:12px; align-items:center;">
                                <img src="{{ optional($p->employee)->avatar ? asset('storage/' . $p->employee->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode(optional($p->employee)->name) }}" alt="avatar" style="width:72px;height:72px;border-radius:12px;object-fit:cover;">
                                <div>
                                    <h3 style="margin:0 0 6px;">{{ optional($p->employee)->name }}</h3>
                                    <div class="muted">Mã NV: {{ optional($p->employee)->employee_code ?? 'N/A' }}</div>
                                    <div class="muted">{{ optional($p->employee->department)->name ?? 'Phòng ban: N/A' }} • {{ optional($p->employee)->position ?? 'Chức vụ: N/A' }}</div>
                                    <div style="margin-top:8px; font-weight:700;">Tháng: {{ $p->display_month }}</div>
                                </div>
                            </div>

                            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(140px,1fr)); gap:12px; margin-top:12px;">
                                <div class="card" style="padding:12px;">
                                    <div class="muted">Lương cơ bản</div>
                                    <div style="font-weight:800;">{{ number_format($p->base_salary ?? 0,0,'.',',') }} VNĐ</div>
                                </div>
                                <div class="card" style="padding:12px;">
                                    <div class="muted">Ngày công</div>
                                    <div style="font-weight:800;">{{ $p->working_days ?? 0 }}</div>
                                </div>
                                <div class="card" style="padding:12px;">
                                    <div class="muted">OT</div>
                                    <div style="font-weight:800;">{{ $p->overtime_hours ?? 0 }} giờ</div>
                                </div>
                                <div class="card" style="padding:12px;">
                                    <div class="muted">Thưởng</div>
                                    <div style="font-weight:800; color:#166534;">+ {{ number_format($p->bonus ?? 0,0,'.',',') }} VNĐ</div>
                                </div>
                                <div class="card" style="padding:12px;">
                                    <div class="muted">Phụ cấp</div>
                                    <div style="font-weight:800; color:#166534;">+ {{ number_format($p->allowance ?? 0,0,'.',',') }} VNĐ</div>
                                </div>
                                <div class="card" style="padding:12px;">
                                    <div class="muted">Khấu trừ</div>
                                    <div style="font-weight:800; color:#dc2626;">- {{ number_format($p->deduction ?? 0,0,'.',',') }} VNĐ</div>
                                </div>
                                <div class="card" style="padding:12px;">
                                    <div class="muted">BHXH</div>
                                    <div style="font-weight:800;">- {{ number_format($p->insurance ?? 0,0,'.',',') }} VNĐ</div>
                                </div>
                                <div class="card" style="padding:12px;">
                                    <div class="muted">Thuế</div>
                                    <div style="font-weight:800;">- {{ number_format($p->tax ?? 0,0,'.',',') }} VNĐ</div>
                                </div>
                            </div>

                            <div style="margin-top:12px; padding:12px; border-radius:8px; background:#f8fafc; display:flex; justify-content:space-between; align-items:center;">
                                <div class="muted">Thực lĩnh</div>
                                <div style="font-size:22px; font-weight:900; color:#2563eb;">{{ number_format($p->total_salary ?? 0,0,'.',',') }} VNĐ</div>
                            </div>

                            <div style="margin-top:12px; display:flex; gap:8px; align-items:center;">
                                @if($p->confirmation_status === 'confirmed')
                                    <span class="badge bg-success">Đã xác nhận</span>
                                @elseif($p->confirmation_status === 'issue_reported')
                                    <span class="badge bg-danger">Có phản hồi</span>
                                @else
                                    <span class="badge bg-warning text-dark">Chờ xác nhận</span>
                                @endif

                                @if($p->confirmation_deadline)
                                    <div class="muted" style="margin-left:8px;">Hạn xác nhận: {{ $p->confirmation_deadline->format('d/m/Y') }}</div>
                                @endif
                            </div>

                            <div style="margin-top:12px; display:flex; gap:8px; flex-wrap:wrap;">
                                @if($p->status !== 'paid' && $p->confirmation_status !== 'confirmed')
                                    <form method="POST" action="{{ route('me.payroll.confirm', $p) }}">
                                        @csrf
                                        <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition" type="submit">Xác nhận bảng lương</button>
                                    </form>

                                    <details class="rounded-lg border border-gray-200 bg-white" style="width:auto;">
                                        <summary class="px-4 py-2 cursor-pointer text-gray-700 hover:bg-gray-100 rounded-lg">Báo cáo sai sót</summary>
                                        <div class="p-4">
                                            <form method="POST" action="{{ route('me.payroll.report_issue', $p) }}" enctype="multipart/form-data">
                                                @csrf
                                                <div class="mb-4">
                                                    <label class="block text-sm font-semibold mb-2">Loại lỗi</label>
                                                    <select name="issue_type" class="w-full rounded-lg border border-gray-300 px-3 py-2">
                                                        <option value="amount">Số tiền</option>
                                                        <option value="attendance">Ngày công/OT</option>
                                                        <option value="other">Khác</option>
                                                    </select>
                                                </div>
                                                <div class="mb-4">
                                                    <label class="block text-sm font-semibold mb-2">Mô tả</label>
                                                    <textarea name="issue_report" class="w-full rounded-lg border border-gray-300 px-3 py-2" rows="4" required></textarea>
                                                </div>
                                                <div class="mb-4">
                                                    <label class="block text-sm font-semibold mb-2">Đính kèm (tùy chọn)</label>
                                                    <input type="file" name="attachment" class="w-full rounded-lg border border-gray-300 px-3 py-2" />
                                                </div>
                                                <button class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition" type="submit">Gửi báo cáo</button>
                                            </form>
                                        </div>
                                    </details>

                                    <a class="px-4 py-2 bg-gray-100 text-gray-900 rounded-lg hover:bg-gray-200 transition" href="#">Tải PDF</a>
                                @else
                                    <a class="px-4 py-2 bg-gray-100 text-gray-900 rounded-lg hover:bg-gray-200 transition" href="#">Xem PDF</a>
                                @endif
                            </div>
                        </div>

                        <aside style="width:320px;">
                            <div class="card" style="padding:12px;">
                                <h4 style="margin-top:0;">Lịch sử</h4>
                                <div style="display:flex; flex-direction:column; gap:8px;">
                                    @if($p->sent_at)
                                        <div><i class="bi bi-envelope"></i> Đã gửi: {{ $p->sent_at->format('d/m/Y H:i') }}</div>
                                    @endif
                                    @if($p->confirmed_at)
                                        <div><i class="bi bi-check-circle"></i> Đã xác nhận: {{ $p->confirmed_at->format('d/m/Y H:i') }}</div>
                                    @endif
                                    @if($p->issue_reported_at)
                                        <div><i class="bi bi-chat-left-text"></i> Phản hồi: {{ $p->issue_reported_at->format('d/m/Y H:i') }}</div>
                                    @endif
                                    <div class="muted">Trạng thái email: {{ $p->email_status ?? 'Chưa gửi' }}</div>
                                </div>
                            </div>
                        </aside>
                    </div>
                @endforeach
            </div>

            <div>
                <div class="card" style="position:sticky; top:20px;">
                    <h4 style="margin-top:0;">Thông tin tài khoản</h4>
                    @php $userEmp = optional($payrolls->first()->employee); @endphp
                    <div style="display:flex; gap:12px; align-items:center;">
                        <img src="{{ $userEmp && $userEmp->avatar ? asset('storage/' . $userEmp->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode(optional($userEmp)->name) }}" alt="avatar" style="width:56px;height:56px;border-radius:8px;object-fit:cover;">
                        <div>
                            <div style="font-weight:800;">{{ optional($userEmp)->name }}</div>
                            <div class="muted">Mã NV: {{ optional($userEmp)->employee_code ?? 'N/A' }}</div>
                            <div class="muted">{{ optional($userEmp->department)->name ?? '' }}</div>
                        </div>
                    </div>

                    <hr>
                    <h5 style="margin:0 0 8px;">Hoạt động gần đây</h5>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        @foreach($payrolls as $p)
                            <div style="font-size:14px;">
                                <strong>{{ $p->display_month }}</strong>
                                <div class="muted">Trạng thái: {{ ucfirst($p->confirmation_status ?? 'Chưa') }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

@endsection
