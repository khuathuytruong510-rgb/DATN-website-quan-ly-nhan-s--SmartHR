<h1>Ghi chấm công</h1>

<form method="POST" action="{{ route('me.attendance.store') }}">
    @csrf
    <div>
        <label>Ngày</label>
        <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required />
    </div>
    <div>
        <label>Trạng thái</label>
        <select name="status">
            <option value="present">Present</option>
            <option value="late">Late</option>
            <option value="leave">Leave</option>
            <option value="absent">Absent</option>
        </select>
    </div>
    <div>
        <label>Ghi chú</label>
        <textarea name="notes"></textarea>
    </div>
    <button type="submit">Ghi</button>
</form>
