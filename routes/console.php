<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$payrollFinalizeAt = Carbon\Carbon::createFromFormat('H:i', config('overtime.shift_end', '17:30'))
    ->addHours(3)
    ->format('H:i');

Schedule::command('payroll:auto-calculate-monthly')->monthlyOn(15, '00:15')->withoutOverlapping();
Schedule::command('payroll:auto-finalize-monthly')->monthlyOn(15, $payrollFinalizeAt)->withoutOverlapping();
Schedule::command('payroll:auto-ready')->monthlyOn(15, '23:59')->withoutOverlapping();
Schedule::command('payroll:auto-lock-period')->dailyAt('00:05');
Schedule::command('contracts:send-expiry-alerts')->dailyAt('08:00');
