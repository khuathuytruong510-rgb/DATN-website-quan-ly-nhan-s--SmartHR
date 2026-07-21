<?php

namespace Tests\Unit;

use App\Services\PaymentService;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Payroll;
use App\Models\SalaryPayment;
use App\Models\SalaryPaymentBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PaymentService();
    }

    private function createEmployeeWithPayroll(array $overrides = []): array
    {
        $dept = Department::create(['name' => 'IT', 'code' => 'IT', 'manager' => 'Manager']);
        $emp = Employee::create(array_merge([
            'name' => 'Nguyen Van A',
            'email' => 'a@test.com',
            'position' => 'Developer',
            'department_id' => $dept->id,
            'status' => 'active',
            'bank_name' => 'Vietcombank',
            'bank_account_number' => '123456789',
            'bank_account_holder' => 'NGUYEN VAN A',
        ], $overrides));

        $payroll = Payroll::create([
            'employee_id' => $emp->id,
            'month' => 7,
            'year' => 2026,
            'base_salary' => 7800000,
            'daily_salary' => 300000,
            'working_salary' => 7800000,
            'overtime_salary' => 500000,
            'allowance' => 500000,
            'bonus' => 500000,
            'insurance' => 819000,
            'tax' => 200000,
            'deduction' => 0,
            'total_salary' => 8281000,
            'status' => 'approved',
        ]);

        return compact('emp', 'payroll');
    }

    public function test_create_payment_from_payroll(): void
    {
        ['emp' => $emp, 'payroll' => $payroll] = $this->createEmployeeWithPayroll();

        $payment = $this->service->createPaymentFromPayroll($payroll);

        $this->assertNotNull($payment);
        $this->assertEquals($emp->id, $payment->employee_id);
        $this->assertEquals($payroll->id, $payment->payroll_id);
        $this->assertEquals('pending', $payment->status);
        $this->assertEquals(8281000, $payment->net);
        $this->assertEquals('bank_transfer', $payment->payment_method);
        $this->assertEquals('Vietcombank', $payment->bank);
        $this->assertEquals('123456789', $payment->account_number);
        $this->assertEquals('unreconciled', $payment->reconciliation_status);
    }

    public function test_create_payment_prevents_duplicates(): void
    {
        ['payroll' => $payroll] = $this->createEmployeeWithPayroll();

        $p1 = $this->service->createPaymentFromPayroll($payroll);
        $p2 = $this->service->createPaymentFromPayroll($payroll);

        $this->assertEquals($p1->id, $p2->id);
        $this->assertEquals(1, SalaryPayment::count());
    }

    public function test_process_payment(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        ['payroll' => $payroll] = $this->createEmployeeWithPayroll();
        $payment = $this->service->createPaymentFromPayroll($payroll);

        $processed = $this->service->processPayment($payment, [
            'payment_method' => 'bank_transfer',
            'transaction_code' => 'TXN-001',
        ]);

        $this->assertEquals('paid', $processed->status);
        $this->assertNotNull($processed->paid_at);
        $this->assertEquals('TXN-001', $processed->transaction_code);

        $payroll->refresh();
        $this->assertEquals('paid', $payroll->status);
    }

    public function test_reconcile_payment(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        ['payroll' => $payroll] = $this->createEmployeeWithPayroll();
        $payment = $this->service->createPaymentFromPayroll($payroll);
        $this->service->processPayment($payment, []);

        $reconciled = $this->service->reconcile($payment->fresh(), 'Đã KS OK');

        $this->assertEquals('reconciled', $reconciled->reconciliation_status);
        $this->assertNotNull($reconciled->reconciled_at);
        $this->assertEquals('Đã KS OK', $reconciled->reconciliation_notes);
    }

    public function test_mark_discrepancy(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        ['payroll' => $payroll] = $this->createEmployeeWithPayroll();
        $payment = $this->service->createPaymentFromPayroll($payroll);
        $this->service->processPayment($payment, []);

        $discrepancy = $this->service->markDiscrepancy($payment->fresh(), 'Sai lệch 500k');

        $this->assertEquals('discrepancy', $discrepancy->reconciliation_status);
        $this->assertEquals('Sai lệch 500k', $discrepancy->reconciliation_notes);
    }

    public function test_create_batch(): void
    {
        ['payroll' => $p1] = $this->createEmployeeWithPayroll(['email' => 'a@test.com']);
        $dept = $p1->employee->department;
        $emp2 = Employee::create([
            'name' => 'Tran Thi B', 'email' => 'b@test.com',
            'position' => 'Designer', 'department_id' => $dept->id, 'status' => 'active',
            'bank_name' => 'Techcombank', 'bank_account_number' => '987654321',
            'bank_account_holder' => 'TRAN THI B',
        ]);
        $p2 = Payroll::create([
            'employee_id' => $emp2->id, 'month' => 7, 'year' => 2026,
            'base_salary' => 7800000, 'daily_salary' => 300000,
            'working_salary' => 7800000, 'overtime_salary' => 0,
            'allowance' => 500000, 'bonus' => 0, 'insurance' => 819000,
            'tax' => 150000, 'deduction' => 0, 'total_salary' => 7331000,
            'status' => 'approved',
        ]);

        $batch = $this->service->createBatch([$p1->id, $p2->id], 'Batch test');

        $this->assertNotNull($batch);
        $this->assertEquals(2, $batch->total_items);
        $this->assertEquals('pending', $batch->status);
        $this->assertEquals('Batch test', $batch->name);
        $this->assertStringStartsWith('BATCH-', $batch->code);
        $this->assertEquals(2, $batch->payments()->count());
    }

    public function test_get_stats(): void
    {
        ['payroll' => $payroll] = $this->createEmployeeWithPayroll();
        $this->service->createPaymentFromPayroll($payroll);

        $stats = $this->service->getStats(7, 2026);

        $this->assertEquals(1, $stats['total']);
        $this->assertEquals(1, $stats['pending']);
        $this->assertEquals(0, $stats['paid']);
    }

    public function test_generate_qr_code(): void
    {
        ['payroll' => $payroll] = $this->createEmployeeWithPayroll();
        $payment = $this->service->createPaymentFromPayroll($payroll);

        $qr = $this->service->generateQrCode($payment);

        $this->assertNotEmpty($qr);
        $this->assertIsString($qr);
        $this->assertStringContainsString('000201', $qr);
    }

    public function test_export_csv(): void
    {
        ['payroll' => $payroll] = $this->createEmployeeWithPayroll();
        $this->service->createPaymentFromPayroll($payroll);

        $csv = $this->service->exportCsv(7, 2026);

        $this->assertNotEmpty($csv);
        $this->assertStringContainsString('Mã phiếu', $csv);
        $this->assertStringContainsString('Nguyen Van A', $csv);
    }

    public function test_record_log(): void
    {
        ['payroll' => $payroll] = $this->createEmployeeWithPayroll();
        $payment = $this->service->createPaymentFromPayroll($payroll);

        $this->actingAs(\App\Models\User::factory()->create());
        $log = $this->service->recordLog($payment, 'test_action', 'Test note');

        $this->assertNotNull($log);
        $this->assertEquals('test_action', $log->action);
        $this->assertEquals('Test note', $log->notes);
    }
}
