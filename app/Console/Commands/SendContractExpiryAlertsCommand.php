<?php

namespace App\Console\Commands;

use App\Services\ContractExpiryAlertService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendContractExpiryAlertsCommand extends Command
{
    protected $signature = 'contracts:send-expiry-alerts {--date= : Giả lập ngày hiện tại (Y-m-d) để demo}';

    protected $description = 'Kiểm tra hợp đồng hết hạn theo mốc 30 ngày / 7 ngày / hết hạn / quá hạn và gửi thông báo (không trùng mốc)';

    public function handle(ContractExpiryAlertService $alerts): int
    {
        $today = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))->startOfDay()
            : now()->startOfDay();

        Carbon::setTestNow($today);

        try {
            $count = $alerts->dispatch($today);
        } finally {
            Carbon::setTestNow();
        }

        $this->info("Đã gửi {$count} cảnh báo hết hạn hợp đồng (ngày {$today->toDateString()}).");

        return self::SUCCESS;
    }
}
