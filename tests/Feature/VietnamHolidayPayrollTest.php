<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Services\PayrollCalculationService;
use App\Services\VietnamHolidayCalendar;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VietnamHolidayPayrollTest extends TestCase
{
    use RefreshDatabase;

    public function test_2026_legal_holidays_include_tet_national_days_and_culture_day(): void
    {
        $dates = collect(app(VietnamHolidayCalendar::class)->buildYear(2026))->pluck('date');

        $this->assertTrue($dates->contains('2026-01-01'));
        $this->assertTrue($dates->contains('2026-02-16'));
        $this->assertTrue($dates->contains('2026-02-17'));
        $this->assertTrue($dates->contains('2026-02-20'));
        $this->assertTrue($dates->contains('2026-04-26'));
        $this->assertTrue($dates->contains('2026-04-27'));
        $this->assertTrue($dates->contains('2026-04-30'));
        $this->assertTrue($dates->contains('2026-05-01'));
        $this->assertTrue($dates->contains('2026-09-01'));
        $this->assertTrue($dates->contains('2026-09-02'));
        $this->assertTrue($dates->contains('2026-11-24'));
    }

    public function test_april_2026_full_attendance_gets_paid_holidays_not_unpaid_gap(): void
    {
        $employee = $this->employeeWithContract();
        $this->seedWorkingDays($employee, 4, 2026, skipDates: ['2026-04-27', '2026-04-30']);

        $amounts = app(PayrollCalculationService::class)->buildAmounts($employee, 4, 2026);

        $this->assertSame(24, $amounts['calendar_working_days']);
        $this->assertEquals(24, $amounts['working_days']);
        $this->assertEquals(2, $amounts['paid_holiday_days']);
        $this->assertEquals(0, $amounts['unpaid_leave_days']);
        $this->assertEquals(0, $amounts['holiday_work_salary']);
    }

    public function test_september_2026_pays_national_day_even_without_attendance(): void
    {
        $employee = $this->employeeWithContract(13000000);
        $amounts = app(PayrollCalculationService::class)->buildAmounts($employee, 9, 2026);
        $daily = 13000000 / 26;
        $meta = app(PayrollCalculationService::class)->periodMeta(9, 2026);

        $this->assertSame(24, $amounts['calendar_working_days']);
        $this->assertEquals(2, $amounts['paid_holiday_days']);
        $this->assertEquals(0, $amounts['working_days']);
        $this->assertEqualsWithDelta($daily * 2, $amounts['paid_holiday_salary'], 1);
        $this->assertEqualsWithDelta($daily * 2, $amounts['working_salary'], 1);
        $this->assertStringContainsString('hưởng lương', $meta['formula_label']);
        $this->assertStringContainsString('01/09', $meta['formula_label']);
        $this->assertStringContainsString('02/09', $meta['formula_label']);
    }

    public function test_working_on_holiday_pays_300_percent_extra_plus_holiday_pay(): void
    {
        $employee = $this->employeeWithContract(45000000);
        $this->seedWorkingDays($employee, 4, 2026, skipDates: ['2026-04-27']);

        $amounts = app(PayrollCalculationService::class)->buildAmounts($employee, 4, 2026);
        $daily = 45000000 / 26;

        $this->assertEquals(24, $amounts['working_days']);
        $this->assertEquals(2, $amounts['paid_holiday_days']);
        $this->assertEqualsWithDelta($daily * 3, $amounts['holiday_work_salary'], 1);
    }

    private function employeeWithContract(float $base = 10000000): Employee
    {
        $department = Department::create(['name' => 'IT', 'code' => 'ITH', 'manager' => 'M']);
        $employee = Employee::create([
            'name' => 'NV Le',
            'email' => 'nvle@example.com',
            'position' => 'Dev',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMPLE',
        ]);
        Contract::create([
            'employee_id' => $employee->id,
            'title' => 'HĐ',
            'salary' => $base,
            'base_salary' => $base,
            'allowance' => 0,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        return $employee;
    }

    private function seedWorkingDays(Employee $employee, int $month, int $year, array $skipDates = []): void
    {
        $cursor = Carbon::create($year, $month, 1);
        $end = $cursor->copy()->endOfMonth();
        for ($day = $cursor->copy(); $day->lte($end); $day->addDay()) {
            if ($day->isSunday() || in_array($day->toDateString(), $skipDates, true)) {
                continue;
            }
            Attendance::create([
                'employee_id' => $employee->id,
                'date' => $day->toDateString(),
                'check_in' => '08:00:00',
                'check_out' => '17:00:00',
                'status' => 'present',
            ]);
        }
    }
}
