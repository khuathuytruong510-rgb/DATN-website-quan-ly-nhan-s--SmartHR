<?php

namespace App\Console\Commands;

use App\Services\VietnamHolidayCalendar;
use Illuminate\Console\Command;

class SyncVietnamHolidaysCommand extends Command
{
    protected $signature = 'holidays:sync {year?}';

    protected $description = 'Đồng bộ ngày lễ hưởng lương theo Điều 112 BLLĐ vào bảng holidays';

    public function handle(VietnamHolidayCalendar $calendar): int
    {
        $year = (int) ($this->argument('year') ?: now()->year);
        $count = $calendar->syncYear($year);
        $this->info("Đã đồng bộ {$count} ngày lễ năm {$year}.");

        return self::SUCCESS;
    }
}
