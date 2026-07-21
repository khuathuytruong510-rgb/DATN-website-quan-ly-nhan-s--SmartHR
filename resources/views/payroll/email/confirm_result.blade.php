<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận bảng lương</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f8fafc; margin:0; padding:40px 16px; color:#111827; }
        .card { max-width:520px; margin:0 auto; background:#fff; border-radius:12px; padding:28px; box-shadow:0 10px 30px rgba(15,23,42,.08); text-align:center; }
        .ok { color:#166534; }
        .err { color:#991b1b; }
        a { color:#2563eb; }
    </style>
</head>
<body>
    <div class="card">
        <h1 class="{{ $ok ? 'ok' : 'err' }}">{{ $ok ? 'Thành công' : 'Không thành công' }}</h1>
        <p>{{ $message }}</p>
        <p><a href="{{ url('/login') }}">Đăng nhập SmartHR</a></p>
    </div>
</body>
</html>
