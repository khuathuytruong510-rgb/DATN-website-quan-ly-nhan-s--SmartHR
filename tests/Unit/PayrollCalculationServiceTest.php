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

    public function test_progressive_tax_matches_vietnam_pit_brackets_2026(): void
    {
        $service = new PayrollCalculationService();

        // Bậc 1: đến 10tr × 5%
        $this->assertSame(500000.0, $service->calculateTax(10_000_000));
        $this->assertSame(250000.0, $service->calculateTax(5_000_000));

        // Bậc 2: 30tr → 10tr×5% + 20tr×10% = 2.500.000
        $this->assertSame(2_500_000.0, $service->calculateTax(30_000_000));

        // Bậc 3: 60tr → 2.500.000 + 30tr×20% = 8.500.000
        $this->assertSame(8_500_000.0, $service->calculateTax(60_000_000));

        // Bậc 4: 100tr → 8.500.000 + 40tr×30% = 20.500.000
        $this->assertSame(20_500_000.0, $service->calculateTax(100_000_000));

        // Bậc 5: 120tr → 20.500.000 + 20tr×35% = 27.500.000
        $this->assertSame(27_500_000.0, $service->calculateTax(120_000_000));
    }

    public function test_tax_breakdown_lists_each_progressive_bracket_segment(): void
    {
        $service = new PayrollCalculationService();
        $parts = $service->calculateTaxBreakdown(12_000_000);

        // 10tr×5% + 2tr×10% = 700.000
        $this->assertSame(700000.0, $parts['tax']);
        $this->assertCount(2, $parts['segments']);
        $this->assertSame(0.05, $parts['segments'][0]['rate']);
        $this->assertSame(500000.0, $parts['segments'][0]['tax']);
        $this->assertSame(0.10, $parts['segments'][1]['rate']);
        $this->assertSame(200000.0, $parts['segments'][1]['tax']);
    }

    public function test_insurance_breakdown_applies_legal_rates_and_caps(): void
    {
        $service = new PayrollCalculationService();

        $underCap = $service->calculateInsuranceBreakdown(10_000_000);
        $this->assertSame(800000.0, $underCap['bhxh']);
        $this->assertSame(150000.0, $underCap['bhyt']);
        $this->assertSame(100000.0, $underCap['bhtn']);
        $this->assertSame(1050000.0, $underCap['total']);

        // Bảo hiểm xã hội / bảo hiểm y tế trần = 20 × 2.530.000 = 50.600.000 (từ 01/7/2026)
        $overCap = $service->calculateInsuranceBreakdown(60_000_000);
        $this->assertSame(50_600_000.0, $overCap['bhxh_bhyt_base']);
        $this->assertSame(60_000_000.0, $overCap['bhtn_base']);
        $this->assertSame(round(50_600_000 * 0.08, 2), $overCap['bhxh']);
        $this->assertSame(round(50_600_000 * 0.015, 2), $overCap['bhyt']);
        $this->assertSame(round(60_000_000 * 0.01, 2), $overCap['bhtn']);
        $this->assertSame(20 * 5_310_000.0, $overCap['bhtn_cap']);
    }

    public function test_family_deduction_matches_2026_personal_income_tax_law(): void
    {
        $service = new PayrollCalculationService();

        $this->assertSame(15_500_000.0, $service->personalDeduction());
        $this->assertSame(6_200_000.0, $service->dependentDeduction());
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
