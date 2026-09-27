<?php

namespace Tests\Unit;

use App\Services\PayrollCalculationService;
use PHPUnit\Framework\TestCase;

class PayrollCalculationServiceTest extends TestCase
{
    public function test_tax_is_zero_only_when_taxable_income_is_zero_or_negative(): void
    {
        $service = new PayrollCalculationService();

        $this->assertSame(0.0, $service->calculateTax(0));
        $this->assertSame(0.0, $service->calculateTax(-1000));
    }

    public function test_progressive_tax_matches_vietnam_pit_brackets(): void
    {
        $service = new PayrollCalculationService();

        // Bậc 1: đến 5tr × 5%
        $this->assertSame(250000.0, $service->calculateTax(5000000));
        $this->assertSame(150000.0, $service->calculateTax(3000000));

        // Bậc 2: 10tr → 5tr×5% + 5tr×10% = 750.000
        $this->assertSame(750000.0, $service->calculateTax(10000000));

        // Bậc 3: 18tr → 750.000 + 8tr×15% = 1.950.000
        $this->assertSame(1950000.0, $service->calculateTax(18000000));

        // Bậc 4: 32tr → 1.950.000 + 14tr×20% = 4.750.000
        $this->assertSame(4750000.0, $service->calculateTax(32000000));

        // Bậc 5: 52tr → 4.750.000 + 20tr×25% = 9.750.000
        $this->assertSame(9750000.0, $service->calculateTax(52000000));

        // Bậc 6: 80tr → 9.750.000 + 28tr×30% = 18.150.000
        $this->assertSame(18150000.0, $service->calculateTax(80000000));

        // Bậc 7: 100tr → 18.150.000 + 20tr×35% = 25.150.000
        $this->assertSame(25150000.0, $service->calculateTax(100000000));
    }

    public function test_tax_breakdown_lists_each_progressive_bracket_segment(): void
    {
        $service = new PayrollCalculationService();
        $parts = $service->calculateTaxBreakdown(12_000_000);

        $this->assertSame(1050000.0, $parts['tax']);
        $this->assertCount(3, $parts['segments']);
        $this->assertSame(0.05, $parts['segments'][0]['rate']);
        $this->assertSame(250000.0, $parts['segments'][0]['tax']);
        $this->assertSame(0.10, $parts['segments'][1]['rate']);
        $this->assertSame(500000.0, $parts['segments'][1]['tax']);
        $this->assertSame(0.15, $parts['segments'][2]['rate']);
        $this->assertSame(300000.0, $parts['segments'][2]['tax']);
    }

    public function test_insurance_breakdown_applies_legal_rates_and_caps(): void
    {
        $service = new PayrollCalculationService();

        $underCap = $service->calculateInsuranceBreakdown(10_000_000);
        $this->assertSame(800000.0, $underCap['bhxh']);
        $this->assertSame(150000.0, $underCap['bhyt']);
        $this->assertSame(100000.0, $underCap['bhtn']);
        $this->assertSame(1050000.0, $underCap['total']);

        // BHXH/BHYT trần = 20 × 2.340.000 = 46.800.000
        $overCap = $service->calculateInsuranceBreakdown(60_000_000);
        $this->assertSame(46_800_000.0, $overCap['bhxh_bhyt_base']);
        $this->assertSame(60_000_000.0, $overCap['bhtn_base']);
        $this->assertSame(round(46_800_000 * 0.08, 2), $overCap['bhxh']);
        $this->assertSame(round(46_800_000 * 0.015, 2), $overCap['bhyt']);
        $this->assertSame(round(60_000_000 * 0.01, 2), $overCap['bhtn']);
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
