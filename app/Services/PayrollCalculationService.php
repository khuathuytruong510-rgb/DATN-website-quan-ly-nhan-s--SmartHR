<?php

namespace App\Services;

class PayrollCalculationService
{
    private PayrollService $payrollService;

    public function __construct(?PayrollService $payrollService = null)
    {
        $this->payrollService = $payrollService ?? new PayrollService();
    }

    public function calculate($employee, int $month, int $year)
    {
        return $this->payrollService->calculate($employee, $month, $year);
    }
}
