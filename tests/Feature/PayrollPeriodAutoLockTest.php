<?php

namespace Tests\Feature;

use App\Models\PayrollPeriodLock;
use App\Models\User;
use App\Services\PayrollPeriodLockService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollPeriodAutoLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_auto_locks_completed_months_only(): void
    {
        User::factory()->create(['is_hr' => true]);

        $service = app(PayrollPeriodLockService::class);
        $asOf = Carbon::create(2026, 9, 3, 0, 5);

        $count = $service->autoLockCompletedPeriods($asOf);

        $this->assertGreaterThan(0, $count);
        $this->assertTrue($service->isLocked(8, 2026));
        $this->assertTrue($service->isLocked(7, 2026));
        $this->assertFalse($service->isLocked(9, 2026));

        $lock = PayrollPeriodLock::query()->where('month', 8)->where('year', 2026)->first();
        $this->assertNotNull($lock);
        $this->assertTrue($lock->is_locked);
        $this->assertNull($lock->locked_by);
    }

    public function test_artisan_command_locks_previous_month(): void
    {
        User::factory()->create(['is_hr' => true]);

        $this->artisan('payroll:auto-lock-period', ['--date' => '2026-09-01'])
            ->assertSuccessful();

        $this->assertTrue(app(PayrollPeriodLockService::class)->isLocked(8, 2026));
        $this->assertFalse(app(PayrollPeriodLockService::class)->isLocked(9, 2026));
    }

    public function test_hr_payroll_index_shows_verify_when_locked(): void
    {
        $hr = User::factory()->create(['is_hr' => true, 'is_admin' => false, 'is_accountant' => false, 'is_director' => false]);

        PayrollPeriodLock::create([
            'month' => 8,
            'year' => 2026,
            'is_locked' => true,
            'locked_at' => now(),
        ]);

        $this->actingAs($hr)
            ->get(route('payroll.index', ['month' => 8, 'year' => 2026]))
            ->assertOk()
            ->assertSee('Đã kiểm tra nguồn — gửi kế toán tính')
            ->assertSee('Yêu cầu mở khóa');
    }
}
