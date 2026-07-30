<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncEmployeeStatus extends Command
{
    protected $signature = 'app:sync-employee-status';
    protected $description = 'Tự động cập nhật status nhân viên: inactive khi hợp đồng hết hạn/chấm dứt';

    public function handle(): int
    {
        $today = Carbon::today()->toDateString();

        $deactivated = Employee::where('status', 'active')
            ->whereHas('activeContract', function ($q) use ($today) {
                $q->where('end_date', '<', $today);
            })
            ->update(['status' => 'inactive']);

        $terminated = Employee::where('status', 'active')
            ->whereHas('activeContract', function ($q) {
                $q->where('status', 'terminated');
            })
            ->update(['status' => 'inactive']);

        $this->info("Đã inactive {$deactivated} nhân viên hết hạn hợp đồng.");
        $this->info("Đã inactive {$terminated} nhân viên chấm dứt hợp đồng.");

        return Command::SUCCESS;
    }
}
