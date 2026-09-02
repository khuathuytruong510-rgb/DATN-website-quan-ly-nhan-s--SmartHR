<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\User;
use App\Services\AttendanceCalculationService;
use App\Services\OvertimeActualCalculator;
use App\Services\OvertimeRequestService;
use App\Services\PayrollCalculationService;
use App\Services\VietnamHolidayCalendar;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OvertimeActualPayrollTest extends TestCase
{
    use RefreshDatabase;

    public function test_actual_ot_is_intersection_of_checkout_and_approved_window(): void
    {
        $calc = new OvertimeActualCalculator();
        $ot = new OvertimeRequest([
            'date' => '2026-09-02',
            'approved_start' => '17:30:00',
            'approved_end' => '20:00:00',
        ]);

        $capped = $calc->compute($ot, Carbon::parse('2026-09-02 20:37:00'));
        $this->assertSame(150, $capped['actual_minutes']);
        $this->assertSame('17:30:00', $capped['actual_start']);
        $this->assertSame('20:00:00', $capped['actual_end']);

        $early = $calc->compute($ot, Carbon::parse('2026-09-02 19:42:00'));
        $this->assertSame(132, $early['actual_minutes']);

        $beforeWindow = $calc->compute($ot, Carbon::parse('2026-09-02 17:10:00'));
        $this->assertSame(0, $beforeWindow['actual_minutes']);

        $shifted = new OvertimeRequest([
            'date' => '2026-09-02',
            'approved_start' => '18:00:00',
            'approved_end' => '20:00:00',
        ]);
        $lateStart = $calc->compute($shifted, Carbon::parse('2026-09-02 19:40:00'));
        $this->assertSame(100, $lateStart['actual_minutes']);
        $this->assertSame('18:00:00', $lateStart['actual_start']);
    }

    public function test_late_checkout_without_approved_ot_is_not_overtime(): void
    {
        $employee = $this->employee();
        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'date' => '2026-09-02',
            'check_in' => '08:02:00',
            'check_out' => '19:42:00',
            'status' => 'present',
        ]);

        app(AttendanceCalculationService::class)->updateAttendanceMetrics($attendance);

        $this->assertEquals(0, (float) $attendance->fresh()->overtime_hours);
        $this->assertSame(0, OvertimeRequest::count());
    }

    public function test_payroll_uses_verified_actual_minutes_not_approved_window(): void
    {
        ['hr' => $hr, 'employee' => $employee] = $this->people();
        $service = app(OvertimeRequestService::class);
        $day = $this->nextPayableWorkday();
        $date = $day->toDateString();

        $ot = $service->assign($hr, [
            'employee_id' => $employee->id,
            'date' => $date,
            'start_time' => '17:30',
            'end_time' => '20:00',
            'reason' => 'Cuối tháng',
        ]);

        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'date' => $date,
            'check_in' => '08:02:00',
            'check_out' => '19:42:00',
            'status' => 'present',
            'overtime_hours' => 3.07,
        ]);

        $ot = $service->applyFromAttendance($attendance->fresh());
        $this->assertSame(OvertimeRequest::STATUS_COMPLETED, $ot->status);
        $this->assertSame(132, (int) $ot->actual_minutes);
        $this->assertEquals(3.07, (float) $attendance->fresh()->overtime_hours);

        $beforeVerify = app(PayrollCalculationService::class)->buildAmounts($employee, (int) $day->month, (int) $day->year);
        $this->assertEquals(0.0, $beforeVerify['overtime_hours']);

        $service->verify($ot, $hr);
        $this->assertSame(OvertimeRequest::STATUS_VERIFIED, $ot->fresh()->status);
        $this->assertEqualsWithDelta(2.2, (float) $attendance->fresh()->overtime_hours, 0.01);

        $amounts = app(PayrollCalculationService::class)->buildAmounts($employee, (int) $day->month, (int) $day->year);
        $this->assertEqualsWithDelta(2.2, $amounts['overtime_hours'], 0.01);
    }

    public function test_legacy_attendance_overtime_still_counts_when_no_request_exists(): void
    {
        $employee = $this->employee();
        Attendance::create([
            'employee_id' => $employee->id,
            'date' => '2026-08-03',
            'check_in' => '08:00:00',
            'check_out' => '17:00:00',
            'status' => 'present',
            'overtime_hours' => 2.5,
        ]);

        $amounts = app(PayrollCalculationService::class)->buildAmounts($employee, 8, 2026);
        $this->assertEqualsWithDelta(2.5, $amounts['overtime_hours'], 0.01);
    }

    private function nextPayableWorkday(): Carbon
    {
        $calendar = app(VietnamHolidayCalendar::class);
        $payroll = app(PayrollCalculationService::class);
        $day = now()->startOfDay();
        for ($i = 0; $i < 21; $i++) {
            $candidate = $day->copy()->addDays($i);
            $holidays = $calendar->mapForMonth((int) $candidate->month, (int) $candidate->year);
            if (! $payroll->isWeeklyOff($candidate) && ! isset($holidays[$candidate->toDateString()])) {
                return $candidate;
            }
        }

        $this->fail('Không tìm được ngày làm việc để kiểm tra OT.');
    }

    /**
     * @return array{hr: User, employee: Employee}
     */
    private function people(): array
    {
        $hr = User::factory()->create(['is_hr' => true, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);
        $employee = $this->employee();
        $employee->update(['user_id' => User::factory()->create([
            'is_hr' => false,
            'is_admin' => false,
            'is_accountant' => false,
            'is_director' => false,
        ])->id]);

        return compact('hr', 'employee');
    }

    private function employee(): Employee
    {
        $department = Department::create(['name' => 'IT', 'code' => 'OTD', 'manager' => 'M']);

        $employee = Employee::create([
            'name' => 'Nguyễn Văn A',
            'email' => 'ot-'.uniqid().'@example.com',
            'position' => 'Dev',
            'department_id' => $department->id,
            'status' => 'active',
            'employee_code' => 'OT'.substr(uniqid(), -5),
        ]);

        Contract::create([
            'employee_id' => $employee->id,
            'title' => 'HĐ',
            'contract_type' => 'fixed_term',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'salary' => 10000000,
            'base_salary' => 10000000,
            'status' => Contract::STATUS_ACTIVE,
        ]);

        return $employee;
    }
}
