<?php

namespace Tests\Unit;

use App\Services\PayrollCalculationService;
use PHPUnit\Framework\TestCase;

class PayrollCalculationServiceTest extends TestCase
{
    public function test_tax_is_zero_for_low_taxable_income(): void
    {
        $service = new PayrollCalculationService();

        $this->assertSame(0.0, $service->calculateTax(5000000));
        $this->assertSame(0.0, $service->calculateTax(3000000));
        $this->assertSame(0.0, $service->calculateTax(0));
    }

    public function test_progressive_tax_is_calculated_correctly(): void
    {
        $service = new PayrollCalculationService();

        $this->assertSame(0.0, $service->calculateTax(5000000));
        $this->assertSame(250000.0, $service->calculateTax(10000000));
        $this->assertSame(1050000.0, $service->calculateTax(18000000));
        $this->assertSame(3150000.0, $service->calculateTax(32000000));
        $this->assertSame(7150000.0, $service->calculateTax(52000000));
    }

    public function test_overtime_hours_are_normalized_to_real_hours(): void
    {
        $service = new PayrollCalculationService();

        $this->assertSame(2.0, $service->normalizeOvertimeHours(120));
        $this->assertSame(2.0, $service->normalizeOvertimeHours(7200));
        $this->assertSame(2.5, $service->normalizeOvertimeHours(2.5));
        $this->assertSame(1.5, $service->normalizeOvertimeHours('01:30:00'));
    }

    public function test_net_salary_is_never_negative(): void
    {
        $service = new PayrollCalculationService();

        $this->assertSame(0.0, $service->calculateNetSalary(1000000, 2000000, 500000));
        $this->assertSame(500000.0, $service->calculateNetSalary(2000000, 1000000, 500000));
    }
}
