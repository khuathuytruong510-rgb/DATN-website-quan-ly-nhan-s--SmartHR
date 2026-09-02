<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\PayrollCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollWorkingDaysTest extends TestCase
{
    use RefreshDatabase;

    public function test_august_2026_off_only_sunday_and_full_attendance_has_zero_leave(): void
    {
        $service = app(PayrollCalculationService::class);
        $period = $service->periodMeta(8, 2026);
        $this->assertSame('01/08/2026', $period['start_label']);
        $this->assertSame('31/08/2026', $period['end_label']);
        $this->assertSame(31, $period['days_in_period']);
        $this->assertSame(5, $period['weekend_days']);
        $this->assertSame(26, $period['calendar_working_days']);
        $this->assertSame(26, $service->workingDaysInMonth(8, 2026));

        $employee = $this->employee();
        $this->seedWorkingDays($employee, 8, 2026);

        $amounts = $service->buildAmounts($employee, 8, 2026);

        $this->assertSame(26, $amounts['required_working_days']);
        $this->assertSame(26, $amounts['calendar_working_days']);
        $this->assertSame(26, $amounts['working_days']);
        $this->assertEquals(0, $amounts['paid_leave_days']);
        $this->assertEquals(0, $amounts['unpaid_leave_days']);
    }

    public function test_missing_weekdays_without_leave_count_as_unpaid_absence(): void
    {
        $employee = $this->employee();
        $this->seedWorkingDays($employee, 8, 2026, skip: 2);

        $amounts = app(PayrollCalculationService::class)->buildAmounts($employee, 8, 2026);

        $this->assertSame(24, $amounts['working_days']);
        $this->assertEquals(0, $amounts['paid_leave_days']);
        $this->assertEquals(2, $amounts['unpaid_leave_days']);
    }

    public function test_approved_annual_leave_on_weekdays_counts_as_paid_leave(): void
    {
        $employee = $this->employee();
        $this->seedWorkingDays($employee, 8, 2026, skipDates: ['2026-08-10', '2026-08-11']);
        LeaveRequest::create([
            'employee_id' => $employee->id,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-11',
            'days' => 2,
            'type' => 'annual',
            'status' => 'approved',
        ]);

        $amounts = app(PayrollCalculationService::class)->buildAmounts($employee, 8, 2026);

        $this->assertSame(24, $amounts['working_days']);
        $this->assertEquals(2, $amounts['paid_leave_days']);
        $this->assertEquals(0, $amounts['unpaid_leave_days']);
    }

    public function test_standard_formula_does_not_add_contract_salary_to_gross(): void
    {
        $employee = $this->employee();
        Contract::create([
            'employee_id' => $employee->id,
            'title' => 'HĐ chính thức',
            'salary' => 45000000,
            'base_salary' => 45000000,
            'allowance' => 1000000,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
        $this->seedWorkingDays($employee, 8, 2026);

        $amounts = app(PayrollCalculationService::class)->buildAmounts($employee, 8, 2026);

        $daily = 45000000 / 26;
        $workPay = $daily * 26;
        $gross = $workPay + 1000000 + 500000;
        $insurance = 45000000 * 0.105;
        $tax = app(PayrollCalculationService::class)->calculateTax($gross - $insurance);

        $this->assertEqualsWithDelta($daily, $amounts['daily_salary'], 0.01);
        $this->assertEqualsWithDelta($workPay, $amounts['working_salary'], 0.01);
        $this->assertEquals(500000.0, $amounts['bonus']);
        $this->assertEqualsWithDelta($insurance, $amounts['insurance'], 0.01);
        $this->assertEqualsWithDelta($tax, $amounts['tax'], 0.01);
        $this->assertEqualsWithDelta($gross - $insurance - $tax, $amounts['total_salary'], 1);
        $this->assertEqualsWithDelta($amounts['base_salary'], $amounts['working_salary'], 0.01);
    }

    private function employee(): Employee
    {
        $department = Department::create(['name' => 'IT', 'code' => 'ITD', 'manager' => 'M']);

        return Employee::create([
            'name' => 'NV Cong',
            'email' => 'nvcong@example.com',
            'position' => 'Dev',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMPCONG',
        ]);
    }

    private function seedWorkingDays(Employee $employee, int $month, int $year, int $skip = 0, array $skipDates = []): void
    {
        $cursor = Carbon::create($year, $month, 1);
        $end = $cursor->copy()->endOfMonth();
        $skipped = 0;
        for ($day = $cursor->copy(); $day->lte($end); $day->addDay()) {
            if ($day->isSunday()) {
                continue;
            }
            if (in_array($day->toDateString(), $skipDates, true)) {
                continue;
            }
            if ($skipped < $skip) {
                $skipped++;
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
