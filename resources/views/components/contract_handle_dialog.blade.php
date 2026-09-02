@props(['contract'])
@once
<style>
.contract-handle-dialog { border: none; border-radius: 12px; padding: 0; max-width: 440px; width: calc(100% - 24px); box-shadow: 0 20px 50px rgba(15,23,42,.25); }
.contract-handle-dialog::backdrop { background: rgba(15,23,42,.45); }
.contract-handle-form { padding: 20px 22px 18px; }
.contract-handle-form h3 { margin: 0 0 6px; font-size: 1.05rem; }
.contract-handle-meta { display: grid; gap: 6px; margin: 0 0 12px; }
.contract-handle-meta > div { display: grid; grid-template-columns: 110px 1fr; gap: 8px; font-size: 14px; }
.contract-handle-meta dt { color: #64748b; font-weight: 500; }
.contract-handle-meta dd { margin: 0; font-weight: 600; }
</style>
@endonce
@php
    $typeMap = ['internship'=>'Thực tập','probation'=>'Thử việc','fixed_term'=>'Xác định thời hạn','indefinite'=>'Không xác định thời hạn','official'=>'Chính thức','seasonal'=>'Thời vụ'];
    $latest = $contract->latestExpiryAction;
@endphp
<dialog id="handle-contract-{{ $contract->id }}" class="contract-handle-dialog">
    <form method="POST" action="{{ route('contracts.handle_expiry', $contract) }}" class="contract-handle-form">
        @csrf
        <h3>Xử lý hợp đồng sắp hết hạn</h3>
        <dl class="contract-handle-meta">
            <div><dt>Nhân viên</dt><dd>{{ optional($contract->employee)->name ?? '—' }}</dd></div>
            <div><dt>Hợp đồng</dt><dd>{{ $typeMap[$contract->contract_type] ?? ($contract->title ?: '—') }}</dd></div>
            <div><dt>Hết hạn</dt><dd>{{ optional($contract->end_date)->format('d/m/Y') ?? '—' }}</dd></div>
            <div><dt>Cảnh báo</dt><dd>{{ $contract->alertLabel() }}</dd></div>
        </dl>
        @if($latest)
            <p class="muted" style="margin:0 0 10px;">Lần xử lý gần nhất: <strong>{{ $latest->label() }}</strong> ({{ $latest->created_at?->format('d/m/Y H:i') }})</p>
        @endif
        <fieldset class="field" style="border:0;padding:0;margin:0 0 12px;">
            <label style="display:block;margin-bottom:6px;"><input type="radio" name="decision" value="renew" required {{ old('decision') === 'renew' ? 'checked' : '' }}> Gia hạn hợp đồng</label>
            <label style="display:block;margin-bottom:6px;"><input type="radio" name="decision" value="not_renew" {{ old('decision') === 'not_renew' ? 'checked' : '' }}> Không gia hạn</label>
            <label style="display:block;"><input type="radio" name="decision" value="wait" {{ old('decision', 'wait') === 'wait' ? 'checked' : '' }}> Chờ quyết định</label>
        </fieldset>
        <div class="field">
            <label for="reason-{{ $contract->id }}">Lý do</label>
            <textarea id="reason-{{ $contract->id }}" name="reason" rows="3" placeholder="Bắt buộc khi chọn gia hạn hoặc không gia hạn">{{ old('reason') }}</textarea>
        </div>
        <div class="actions" style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end;">
            <button type="button" class="btn" onclick="this.closest('dialog').close()">Đóng</button>
            <button type="submit" class="btn primary">Lưu xử lý</button>
        </div>
    </form>
</dialog>
