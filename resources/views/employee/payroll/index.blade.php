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
                            <span class="inline-flex rounded-full bg-slate-100 text-slate-700 text-xs font-semibold px-3 py-1">{{ $workflow->statusLabel($p->status) }}</span>
                        </div>
                    </div>

                    @php $em = fn ($v) => number_format((float) $v, 0, '.', ','); @endphp
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 rounded-xl bg-slate-50 border border-slate-100 p-4 mb-4">
                        <div>
                            <div class="text-xs text-gray-500 mb-1">Ngày công</div>
                            <div class="font-bold">{{ $p->working_days ?? 0 }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 mb-1">Lương theo công</div>
                            <div class="font-bold">{{ $em($p->working_salary) }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 mb-1">Phụ cấp + thưởng</div>
                            <div class="font-bold text-green-700">+{{ $em(($p->allowance ?? 0) + ($p->bonus ?? 0)) }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 mb-1">Thực lĩnh</div>
                            <div class="font-extrabold text-blue-600 text-xl">{{ $em($p->total_salary) }} ₫</div>
                        </div>
                    </div>
                    <details class="mb-4 rounded-xl border border-gray-200">
                        <summary class="px-4 py-2.5 cursor-pointer font-semibold text-gray-800">Chi tiết thu nhập / BH / thuế</summary>
                        <div class="p-4 border-t text-sm space-y-2">
                            <div class="flex justify-between"><span>Tổng thu nhập</span><strong>{{ $em($p->gross_salary ?: ((float)$p->working_salary + (float)$p->overtime_salary + (float)$p->allowance + (float)$p->bonus)) }}</strong></div>
                            <div class="flex justify-between text-red-600"><span>BHXH 8%</span><strong>−{{ $em($p->insurance_bhxh) }}</strong></div>
                            <div class="flex justify-between text-red-600"><span>BHYT 1,5%</span><strong>−{{ $em($p->insurance_bhyt) }}</strong></div>
                            <div class="flex justify-between text-red-600"><span>BHTN 1%</span><strong>−{{ $em($p->insurance_bhtn) }}</strong></div>
                            <div class="flex justify-between"><span>GTGC bản thân</span><strong>{{ $em($p->personal_deduction_amount) }}</strong></div>
                            <div class="flex justify-between"><span>GTGC NPT ({{ (int) ($p->dependent_count ?? 0) }})</span><strong>{{ $em($p->dependent_deduction_amount) }}</strong></div>
                            <div class="flex justify-between"><span>TNTT</span><strong>{{ $em($p->taxable_income) }}</strong></div>
                            <div class="flex justify-between text-red-600"><span>Thuế TNCN</span><strong>−{{ $em($p->tax) }}</strong></div>
                            @if(($p->late_penalty_fee ?? 0) > 0)
                            <div class="flex justify-between text-red-600"><span>Phạt đi muộn</span><strong>−{{ $em($p->late_penalty_fee) }}</strong></div>
                            @endif
                        </div>
                    </details>

                    <div class="flex flex-wrap gap-2">
                        @if($workflow->isCalculated($p->status) || $workflow->isHrChecked($p->status))
                            <div class="w-full rounded-xl bg-slate-50 text-slate-700 px-4 py-3 text-sm border border-slate-200">
                                {{ $workflow->statusLabel($p->status) }}. Bạn chỉ xem phiếu ở bước này.
                            </div>
                        @elseif($workflow->isDirectorApproved($p->status))
                            <div class="w-full rounded-xl bg-green-50 text-green-800 px-4 py-3 text-sm border border-green-100">
                                Giám đốc đã duyệt bảng lương{{ $p->sent_at ? ' (cập nhật '.optional($p->sent_at)->format('d/m/Y H:i').')' : '' }}.
                                Bạn chỉ xem chi tiết phiếu đã được phê duyệt.
                            </div>
                        @elseif(in_array($p->status, ['employee_confirmed', 'ready_for_payment', 'paid'], true))
                            <div class="w-full rounded-xl bg-slate-50 text-slate-700 px-4 py-3 text-sm border border-slate-200">
                                {{ $workflow->statusLabel($p->status) }}. Bạn chỉ xem phiếu.
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
@endsection
