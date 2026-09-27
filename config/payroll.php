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
    | Bảo hiểm bắt buộc người lao động đóng (Luật BHXH / BHYT / BHTN):
    | - BHXH (hưu trí + tử tuất): 8%
    | - BHYT: 1,5%
    | - BHTN: 1%
    | Tổng mặc định = 10,5%.
    |
    | Trần đóng:
    | - BHXH + BHYT: tối đa 20 × lương cơ sở
    | - BHTN: tối đa 20 × mức lương tối thiểu vùng
    | (NĐ 73/2024 lương cơ sở; NĐ 74/2024 lương tối thiểu vùng I — chỉnh qua env khi Nhà nước điều chỉnh).
    */
    'insurance' => [
        // Tỷ lệ người lao động đóng (khấu trừ trên phiếu lương)
        'bhxh_rate' => (float) env('PAYROLL_BHXH_RATE', 0.08),
        'bhyt_rate' => (float) env('PAYROLL_BHYT_RATE', 0.015),
        'bhtn_rate' => (float) env('PAYROLL_BHTN_RATE', 0.01),
        // Tỷ lệ người sử dụng lao động đóng (chi phí công ty — không trừ lương)
        'employer_bhxh_rate' => (float) env('PAYROLL_EMPLOYER_BHXH_RATE', 0.175),
        'employer_bhyt_rate' => (float) env('PAYROLL_EMPLOYER_BHYT_RATE', 0.03),
        'employer_bhtn_rate' => (float) env('PAYROLL_EMPLOYER_BHTN_RATE', 0.01),
        'base_salary' => (float) env('PAYROLL_SOCIAL_BASE_SALARY', 2_340_000),
        'regional_minimum_wage' => (float) env('PAYROLL_REGIONAL_MIN_WAGE', 4_960_000),
        'cap_multiplier' => (int) env('PAYROLL_INSURANCE_CAP_MULTIPLIER', 20),
    ],

    /*
    | Tỷ lệ tổng (tương thích ngược). Nếu không set env thì = tổng 3 tỷ lệ trên.
    */
    'insurance_employee_rate' => (float) env(
        'PAYROLL_INSURANCE_RATE',
        (float) env('PAYROLL_BHXH_RATE', 0.08)
        + (float) env('PAYROLL_BHYT_RATE', 0.015)
        + (float) env('PAYROLL_BHTN_RATE', 0.01)
    ),

    /*
    | Giảm trừ gia cảnh trước khi áp dụng biểu thuế lũy tiến từng phần
    | (Nghị quyết 954/2020/UBTVQH14):
    | - Bản thân: 11.000.000 đ/tháng
    | - Người phụ thuộc: 4.400.000 đ/tháng/người
    */
    'personal_deduction' => (float) env('PAYROLL_PERSONAL_DEDUCTION', 11_000_000),
    'dependent_deduction' => (float) env('PAYROLL_DEPENDENT_DEDUCTION', 4_400_000),

    /*
    | Biểu thuế TNCN lũy tiến từng phần (tháng) — Luật thuế TNCN / TT 111/2013/TT-BTC.
    | Mỗi bậc: [ngưỡng trên (null = vô hạn), thuế suất].
    */
    'pit_brackets' => [
        [5_000_000, 0.05],
        [10_000_000, 0.10],
        [18_000_000, 0.15],
        [32_000_000, 0.20],
        [52_000_000, 0.25],
        [80_000_000, 0.30],
        [null, 0.35],
    ],

    'bonus' => [
        'full_attendance_days' => (int) env('PAYROLL_BONUS_FULL_DAYS', 22),
        'good_attendance_days' => (int) env('PAYROLL_BONUS_GOOD_DAYS', 18),
        'full' => (float) env('PAYROLL_BONUS_FULL', 500000),
        'good' => (float) env('PAYROLL_BONUS_GOOD', 300000),
        'base' => (float) env('PAYROLL_BONUS_BASE', 200000),
    ],
];
