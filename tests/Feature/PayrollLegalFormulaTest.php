<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Services\PayrollCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollLegalFormulaTest extends TestCase
{
    use RefreshDatabase;

    public function test_personal_deduction_and_dependents_reduce_taxable_income(): void
    {
        $employee = $this->employee(dependents: 2);
        Contract::create([
            'employee_id' => $employee->id,
            'title' => 'HĐ',
            'salary' => 20_000_000,
            'base_salary' => 20_000_000,
            'allowance' => 0,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
        $this->seedWorkingDays($employee, 8, 2026);

        $service = app(PayrollCalculationService::class);
        $amounts = $service->buildAmounts($employee, 8, 2026);

        $gross = $amounts['working_salary'] + $amounts['overtime_salary'] + $amounts['allowance'] + $amounts['bonus'];
        $insurance = $service->calculateInsurance(20_000_000);
        $family = 11_000_000 + (2 * 4_400_000);
        $taxable = max(0, $gross - $insurance - $family);
        $tax = $service->calculateTax($taxable);

        $this->assertEqualsWithDelta($insurance, $amounts['insurance'], 0.01);
        $this->assertEqualsWithDelta($tax, $amounts['tax'], 0.01);
        $this->assertEqualsWithDelta(19_800_000.0, $family, 0.01);
        $this->assertLessThan($service->calculateTax(max(0, $gross - $insurance)), $amounts['tax']);
    }

    public function test_insurance_cap_applies_when_base_salary_exceeds_ceiling(): void
    {
        $service = app(PayrollCalculationService::class);
        $employee = $this->employee();
        Contract::create([
            'employee_id' => $employee->id,
            'title' => 'HĐ',
            'salary' => 60_000_000,
            'base_salary' => 60_000_000,
            'allowance' => 0,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
        $this->seedWorkingDays($employee, 8, 2026);

        $amounts = $service->buildAmounts($employee, 8, 2026);
        $expected = $service->calculateInsurance(60_000_000);
        $uncapped = 60_000_000 * 0.105;

        $this->assertEqualsWithDelta($expected, $amounts['insurance'], 0.01);
        $this->assertLessThan($uncapped, $amounts['insurance']);
    }

    private function employee(int $dependents = 0): Employee
    {
        $department = Department::create(['name' => 'Legal', 'code' => 'LEG', 'manager' => 'M']);

        return Employee::create([
            'name' => 'NV Luat',
            'email' => 'nvluat@example.com',
            'position' => 'Dev',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'EMPLAW',
            'number_of_dependents' => $dependents,
        ]);
    }

    private function seedWorkingDays(Employee $employee, int $month, int $year): void
    {
        $cursor = Carbon::create($year, $month, 1);
        $end = $cursor->copy()->endOfMonth();
        for ($day = $cursor->copy(); $day->lte($end); $day->addDay()) {
            if ($day->isSunday()) {
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
