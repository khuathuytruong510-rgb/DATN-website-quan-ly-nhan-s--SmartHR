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
    | - Ngày thường: làm thêm giờ ≥ 150%
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
    | Bảo hiểm bắt buộc người lao động đóng (Luật Bảo hiểm xã hội / Bảo hiểm y tế / Bảo hiểm thất nghiệp):
    | - Bảo hiểm xã hội (hưu trí + tử tuất): 8%
    | - Bảo hiểm y tế: 1,5%
    | - Bảo hiểm thất nghiệp: 1%
    | Tổng mặc định = 10,5%.
    |
    | Trần đóng (áp dụng từ 01/7/2026 và 01/1/2026):
    | - Bảo hiểm xã hội + Bảo hiểm y tế: tối đa 20 × mức lương cơ sở 2.530.000 = 50.600.000
    |   (Nghị định 161/2026/NĐ-CP — mức lương cơ sở từ 01/7/2026)
    | - Bảo hiểm thất nghiệp: tối đa 20 × mức lương tối thiểu vùng I 5.310.000 = 106.200.000
    |   (Nghị định 293/2025/NĐ-CP — lương tối thiểu vùng từ 01/1/2026)
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
        'base_salary' => (float) env('PAYROLL_SOCIAL_BASE_SALARY', 2_530_000),
        'regional_minimum_wage' => (float) env('PAYROLL_REGIONAL_MIN_WAGE', 5_310_000),
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
    | (Luật Thuế thu nhập cá nhân 2025 / Văn bản hợp nhất 112/VBHN-VPQH —
    |  áp dụng với tiền lương, tiền công từ kỳ tính thuế năm 2026):
    | - Bản thân: 15.500.000 đồng/tháng
    | - Người phụ thuộc: 6.200.000 đồng/tháng/người
    */
    'personal_deduction' => (float) env('PAYROLL_PERSONAL_DEDUCTION', 15_500_000),
    'dependent_deduction' => (float) env('PAYROLL_DEPENDENT_DEDUCTION', 6_200_000),

    /*
    | Biểu thuế thu nhập cá nhân lũy tiến từng phần (tháng) — 5 bậc
    | (Luật Thuế thu nhập cá nhân 2025, hiệu lực với tiền lương từ kỳ thuế 2026).
    | Mỗi bậc: [ngưỡng trên (null = vô hạn), thuế suất].
    */
    'pit_brackets' => [
        [10_000_000, 0.05],
        [30_000_000, 0.10],
        [60_000_000, 0.20],
        [100_000_000, 0.30],
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
