<?php

namespace Tests\Concerns;

use App\Models\Payroll;
trait AssertsRemovedPayrollPaymentFeatures
{
    protected function assertNamedRouteRemoved(string $name, mixed ...$parameters): void
    {
        try {
            route($name, ...$parameters);
            $this->fail("Route [{$name}] should be removed.");
        } catch (\Symfony\Component\Routing\Exception\RouteNotFoundException) {
            $this->assertTrue(true);
        }
    }

    protected function postEmployeePayrollConfirm(Payroll|int $payroll)
    {
        $id = $payroll instanceof Payroll ? $payroll->id : $payroll;

        return $this->post('/me/payroll/'.$id.'/confirm');
    }

    protected function postPayrollPaymentConfirm(Payroll|int $payroll, array $data = [])
    {
        $id = $payroll instanceof Payroll ? $payroll->id : $payroll;

        return $this->post('/payroll/'.$id.'/payment/confirm', $data);
    }

    protected function getPayrollPaymentScreen(Payroll|int $payroll)
    {
        $id = $payroll instanceof Payroll ? $payroll->id : $payroll;

        return $this->get('/payroll/'.$id.'/payment');
    }
}
