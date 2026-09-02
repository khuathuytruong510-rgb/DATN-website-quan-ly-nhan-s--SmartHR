<?php

return [
    /*
    | Kết thúc ca chuẩn. OT thực tế bắt đầu không sớm hơn mốc này
    | và không sớm hơn giờ OT đã duyệt.
    */
    'shift_end' => env('OVERTIME_SHIFT_END', '17:30'),

    /*
    | Nhân viên chỉ được tự đăng ký hôm nay hoặc ngày mai.
    | HR chỉ định có thể chọn ngày tương lai (không quá khứ).
    */
    'employee_max_days_ahead' => 1,
];
