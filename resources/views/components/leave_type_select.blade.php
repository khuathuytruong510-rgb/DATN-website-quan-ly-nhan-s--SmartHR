@php
    $types = $leaveTypes ?? \App\Support\LeaveTypes::all();
    $selected = old('type', $selected ?? \App\Support\LeaveTypes::default());
@endphp
<select id="leave-type" name="type" class="form-select" required>
    @foreach($types as $value => $meta)
        <option value="{{ $value }}" {{ $selected === $value ? 'selected' : '' }}>{{ $meta['label'] }}</option>
    @endforeach
</select>
