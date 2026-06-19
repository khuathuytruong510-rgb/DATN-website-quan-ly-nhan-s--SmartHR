<h1>Tạo đơn nghỉ phép</h1>

<form method="POST" action="{{ route('me.leave_requests.store') }}">
    @csrf
    <div>
        <label>Ngày bắt đầu</label>
        <input type="date" name="start_date" required />
    </div>
    <div>
        <label>Ngày kết thúc</label>
        <input type="date" name="end_date" required />
    </div>
    <div>
        <label>Loại</label>
        <input type="text" name="type" required />
    </div>
    <div>
        <label>Lý do</label>
        <textarea name="reason"></textarea>
    </div>
    <button type="submit">Gửi</button>
</form>
