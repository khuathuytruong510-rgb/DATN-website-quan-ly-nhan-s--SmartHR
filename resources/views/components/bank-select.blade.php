@php
    $banks = config('banks', []);
    $selected = old($name ?? 'bank_name', $value ?? '');
    $required = $required ?? false;
    $field = $name ?? 'bank_name';
    $class = $class ?? '';
@endphp
<select name="{{ $field }}" @if($required) required @endif class="{{ $class }}" {{ $attributes ?? '' }}>
    <option value="">-- Chọn ngân hàng --</option>
    @foreach($banks as $bank)
        <option value="{{ $bank }}" @selected($selected === $bank)>{{ $bank }}</option>
    @endforeach
    @if(filled($selected) && ! in_array($selected, $banks, true))
        <option value="{{ $selected }}" selected>{{ $selected }} (đã lưu)</option>
    @endif
</select>
