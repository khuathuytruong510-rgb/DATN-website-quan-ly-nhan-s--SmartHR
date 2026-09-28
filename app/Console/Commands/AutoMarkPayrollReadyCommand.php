<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AutoMarkPayrollReadyCommand extends Command
{
    protected $signature = 'payroll:auto-ready';

    protected $description = 'Legacy no-op: đã bỏ bước NV xác nhận / thanh toán lương';

    public function handle(): int
    {
        $this->info('Bỏ qua auto-ready: quy trình dừng ở Giám đốc duyệt và thông báo bảng lương.');

        return self::SUCCESS;
    }
}
