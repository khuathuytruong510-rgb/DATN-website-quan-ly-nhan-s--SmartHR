<?php

return [
    /*
    | Ngày công chuẩn dùng để tính lương ngày (không phải số ngày T2–T6 lịch).
    | Lương ngày = Lương cơ bản / standard_working_days
    */
    'standard_working_days' => (int) env('PAYROLL_STANDARD_DAYS', 26),

    /*
    | Ngày nghỉ hàng tuần. Carbon: 0 = Chủ nhật, 6 = Thứ 7.
    | Chỉ nghỉ Chủ nhật → Thứ 7 vẫn tính ngày công.
    */
    'off_weekdays' => [Carbon\Carbon::SUNDAY],

    'hours_per_day' => (float) env('PAYROLL_HOURS_PER_DAY', 8),

    /*
    | Hệ số theo Điều 98 Bộ luật Lao động 2019:
    | - Ngày thường: OT ≥ 150%
    | - Ngày nghỉ hằng tuần (Chủ nhật): ≥ 200%
    | - Ngày lễ / nghỉ có lương: ≥ 300% (chưa kể 100% lương ngày lễ)
    | - Ban đêm: +30% (chưa tách ca đêm nếu không có dữ liệu)
    */
    'overtime_hour_rate' => (float) env('PAYROLL_OT_HOUR_RATE', 1.5),
    'weekly_rest_rate' => (float) env('PAYROLL_WEEKLY_REST_RATE', 2.0),
    'holiday_work_rate' => (float) env('PAYROLL_HOLIDAY_WORK_RATE', 3.0),

    /*
    | Ngày Văn hóa Việt Nam 24/11 — nghỉ hưởng lương từ 2026
    | (Nghị quyết 80-NQ/TW / chủ trương sửa Điều 112 BLLĐ).
    */
    'vietnam_culture_day_from_year' => 2026,

    /*
    | Bảo hiểm người lao động = mức đóng × tỷ lệ (BHXH + BHYT + BHTN ≈ 10,5%).
    | Mặc định mức đóng = lương cơ bản hợp đồng.
    */
    'insurance_employee_rate' => (float) env('PAYROLL_INSURANCE_RATE', 0.105),

    /*
    | Giảm trừ gia cảnh trước khi áp dụng biểu thuế lũy tiến.
    | 0 = giữ số liệu demo hiện tại. Luật VN: 11.000.000 / người / tháng.
    */
    'personal_deduction' => (float) env('PAYROLL_PERSONAL_DEDUCTION', 0),
    'dependent_deduction' => (float) env('PAYROLL_DEPENDENT_DEDUCTION', 4400000),

    'bonus' => [
        'full_attendance_days' => (int) env('PAYROLL_BONUS_FULL_DAYS', 22),
        'good_attendance_days' => (int) env('PAYROLL_BONUS_GOOD_DAYS', 18),
        'full' => (float) env('PAYROLL_BONUS_FULL', 500000),
        'good' => (float) env('PAYROLL_BONUS_GOOD', 300000),
        'base' => (float) env('PAYROLL_BONUS_BASE', 200000),
    ],
];
