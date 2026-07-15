SELECT 
    p.id,
    e.name as employee_name,
    p.month,
    p.base_salary,
    p.daily_salary,
    p.working_days,
    p.working_salary,
    p.overtime_hours,
    p.overtime_day_salary,
    p.overtime_hour_salary,
    p.overtime_salary,
    p.allowance,
    p.bonus,
    p.insurance,
    p.tax,
    p.total_salary
FROM payrolls p
LEFT JOIN employees e ON p.employee_id = e.id
ORDER BY p.employee_id
LIMIT 5;
