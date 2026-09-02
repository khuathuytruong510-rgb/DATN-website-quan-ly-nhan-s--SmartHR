@extends('layouts.app')

@section('title', 'Bảng lương của tôi')

@section('breadcrumb')
<li><a href="{{ route('me.dashboard') }}">Dashboard</a></li>
<li>Bảng lương</li>
@endsection

@section('content')
<div class="max-w-4xl">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Bảng lương của tôi</h1>
        <p class="text-gray-500 mt-1">Xem, xác nhận phiếu lương và lịch sử thanh toán</p>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 text-green-800 px-4 py-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3">{{ session('error') }}</div>
    @endif

    {{-- Tài khoản nhận lương --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm mb-6">
        <h2 class="text-lg font-bold text-gray-900 mb-1">Tài khoản nhận lương</h2>
        @php $emp = optional($payrolls->first())->employee ?? auth()->user()?->linkedEmployee(); @endphp
        <p class="text-sm text-gray-500 mb-4">Không sửa trực tiếp. Gửi yêu cầu để {{ \App\Support\RequestApprover::queueLabel($emp) }} duyệt trước khi cập nhật.</p>

        @if($emp)
            @php
                $acct = (string) ($emp->account_number ?? '');
                $masked = $acct === '' ? '—' : (strlen($acct) <= 4 ? str_repeat('*', strlen($acct)) : str_repeat('*', max(0, strlen($acct) - 4)).substr($acct, -4));
            @endphp
            <div class="grid md:grid-cols-2 gap-3 text-sm text-gray-700 mb-4 rounded-xl bg-slate-50 border border-slate-100 p-4">
                <div><span class="text-gray-500">Ngân hàng</span><div class="font-semibold">{{ $emp->bank_name ?: '—' }}</div></div>
                <div><span class="text-gray-500">Số tài khoản</span><div class="font-semibold">{{ $masked }}</div></div>
                <div><span class="text-gray-500">Chủ tài khoản</span><div class="font-semibold">{{ $emp->account_holder ?: '—' }}</div></div>
                <div><span class="text-gray-500">Trạng thái</span><div class="font-semibold">{{ $emp->account_number ? 'Đã xác nhận' : 'Chưa có' }}</div></div>
            </div>
        @endif

        <details class="rounded-xl border border-gray-200">
            <summary class="px-4 py-2.5 cursor-pointer font-semibold text-gray-800">Yêu cầu thay đổi</summary>
            <form method="POST" action="{{ route('me.payroll.bank_change') }}" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-3 p-4 border-t">
                @csrf
                <div>
                    <label class="block text-sm font-semibold mb-1">Ngân hàng</label>
                    @include('components.bank-select', [
                        'name' => 'bank_name',
                        'value' => '',
                        'required' => false,
                        'class' => 'w-full rounded-xl border border-gray-300 px-3 py-2',
                    ])
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Số tài khoản</label>
                    <input type="text" name="account_number" class="w-full rounded-xl border border-gray-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Chủ tài khoản</label>
                    <input type="text" name="account_holder" class="w-full rounded-xl border border-gray-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Ảnh QR</label>
                    <input type="file" name="qr_image" accept="image/*" class="w-full rounded-xl border border-gray-300 px-3 py-2">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-1">Lý do</label>
                    <textarea name="note" rows="2" class="w-full rounded-xl border border-gray-300 px-3 py-2"></textarea>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-semibold hover:bg-indigo-700">Gửi yêu cầu thay đổi</button>
                </div>
            </form>
        </details>
    </div>

    @if($payrolls->isEmpty())
        <div class="rounded-2xl border border-gray-200 bg-white p-8 text-gray-500 shadow-sm">
            Chưa có phiếu lương.
        </div>
    @else
        <div class="space-y-4">
            @foreach($payrolls as $p)
                <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Tháng {{ $p->display_month }}</h3>
                            <p class="text-sm text-gray-500 mt-1">{{ optional($p->employee)->position }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if($p->status === 'payroll_issue' || $p->confirmation_status === 'issue_reported')
                                <span class="inline-flex rounded-full bg-amber-100 text-amber-800 text-xs font-semibold px-3 py-1">Đã báo sự cố</span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-100 text-slate-700 text-xs font-semibold px-3 py-1">{{ $workflow->statusLabel($p->status) }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 rounded-xl bg-slate-50 border border-slate-100 p-4 mb-4">
                        <div>
                            <div class="text-xs text-gray-500 mb-1">Ngày công</div>
                            <div class="font-bold">{{ $p->working_days ?? 0 }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 mb-1">Lương cơ bản</div>
                            <div class="font-bold">{{ number_format($p->base_salary ?? 0, 0, '.', ',') }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 mb-1">Phụ cấp</div>
                            <div class="font-bold text-green-700">+{{ number_format($p->allowance ?? 0, 0, '.', ',') }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 mb-1">Khấu trừ</div>
                            <div class="font-bold text-red-600">-{{ number_format(($p->insurance ?? 0)+($p->tax ?? 0)+($p->deduction ?? 0), 0, '.', ',') }}</div>
                        </div>
                        @if(($p->late_penalty_fee ?? 0) > 0)
                        <div>
                            <div class="text-xs text-gray-500 mb-1">Phạt đi muộn</div>
                            <div class="font-bold text-red-600">-{{ number_format($p->late_penalty_fee ?? 0, 0, '.', ',') }} ₫</div>
                        </div>
                        @endif
                        <div>
                            <div class="text-xs text-gray-500 mb-1">Thực lĩnh</div>
                            <div class="font-extrabold text-blue-600 text-xl">{{ number_format($p->total_salary ?? 0, 0, '.', ',') }} ₫</div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @if($p->status === 'payroll_issue' || $p->confirmation_status === 'issue_reported')
                            <div class="w-full rounded-xl bg-amber-50 text-amber-800 px-4 py-3 text-sm border border-amber-200">
                                Đã gửi báo cáo sự cố. Đang chờ HR / kế toán khắc phục, rồi Giám đốc phê duyệt lại.
                            </div>
                        @elseif($workflow->isCalculated($p->status) || $workflow->isHrChecked($p->status))
                            <div class="w-full rounded-xl bg-slate-50 text-slate-700 px-4 py-3 text-sm border border-slate-200">
                                {{ $workflow->statusLabel($p->status) }}. Bạn chỉ xem phiếu ở bước này.
                            </div>
                        @elseif($workflow->isDirectorApproved($p->status) && $p->confirmation_status !== 'confirmed')
                            <div class="w-full rounded-xl bg-blue-50 text-blue-800 px-4 py-3 text-sm border border-blue-100 mb-1">
                                Phiếu lương cần bạn xác nhận{{ $p->sent_at ? ' (đã cập nhật '.optional($p->sent_at)->format('d/m/Y H:i').')' : '' }}.
                            </div>
                            <form method="POST" action="{{ route('me.payroll.confirm', $p) }}">
                                @csrf
                                <button class="px-5 py-2.5 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700" type="submit">
                                    Xác nhận bảng lương
                                </button>
                            </form>
                            <details class="rounded-xl border border-gray-200">
                                <summary class="px-4 py-2.5 cursor-pointer font-semibold text-gray-700">Báo cáo sai sót bảng lương</summary>
                                <form method="POST" action="{{ route('me.payroll.report_issue', $p) }}" class="p-4 border-t">
                                    @csrf
                                    <p class="text-sm font-semibold mb-2">Loại lỗi</p>
                                    <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                                        <label><input type="checkbox" name="issue_types[]" value="working_days"> Sai ngày công</label>
                                        <label><input type="checkbox" name="issue_types[]" value="allowance"> Sai phụ cấp</label>
                                        <label><input type="checkbox" name="issue_types[]" value="deduction"> Sai khấu trừ</label>
                                        <label><input type="checkbox" name="issue_types[]" value="overtime"> Sai OT</label>
                                        <label><input type="checkbox" name="issue_types[]" value="other"> Khác</label>
                                    </div>
                                    <textarea name="issue_report" rows="3" required class="w-full rounded-xl border border-gray-300 px-3 py-2 mb-3" placeholder="Nội dung..."></textarea>
                                    <button class="px-4 py-2 bg-amber-500 text-white rounded-xl font-semibold" type="submit">Gửi báo cáo sai sót</button>
                                </form>
                            </details>
                        @elseif($workflow->canPay($p))
                            <div class="w-full rounded-xl bg-blue-50 text-blue-800 px-4 py-3 text-sm border border-blue-100">
                                Bạn đã xác nhận. Phiếu đang chờ kế toán thanh toán.
                            </div>
                        @elseif($p->status === 'paid')
                            <div class="w-full rounded-xl bg-green-50 text-green-700 px-4 py-3 text-sm">
                                Đã thanh toán{{ $p->paid_at ? ' lúc '.$p->paid_at->format('d/m/Y H:i') : '' }}
                                @if($p->payment_method) · {{ $p->payment_method }} @endif
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Lịch sử thanh toán --}}
        @php $paidList = $payrolls->where('status', 'paid'); @endphp
        @if($paidList->isNotEmpty())
            <div class="mt-8 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold mb-4">Lịch sử thanh toán</h2>
                <div class="space-y-3">
                    @foreach($paidList as $paid)
                        <div class="flex justify-between gap-3 border-b border-gray-100 pb-3 text-sm">
                            <div>
                                <strong>Tháng {{ $paid->display_month }}</strong>
                                <div class="text-gray-500">{{ optional($paid->paid_at)->format('d/m/Y H:i') ?? '—' }} · {{ $paid->payment_method ?? '—' }}</div>
                            </div>
                            <div class="font-bold text-blue-600">{{ number_format($paid->total_salary ?? 0, 0, '.', ',') }} ₫</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
