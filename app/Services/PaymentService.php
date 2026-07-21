<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\SalaryPayment;
use App\Models\SalaryPaymentBatch;
use App\Models\SalaryPaymentLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class PaymentService
{
    public function createPaymentFromPayroll(Payroll $payroll, ?string $notes = null): SalaryPayment
    {
        $employee = $payroll->employee;
        $existing = SalaryPayment::where('payroll_id', $payroll->id)->first();
        if ($existing) {
            return $existing;
        }

        $net = round(($payroll->total_salary ?? 0), 2);

        $payment = SalaryPayment::create([
            'employee_id'     => $payroll->employee_id,
            'payroll_id'      => $payroll->id,
            'code'            => 'PAY-' . now()->format('YmdHis'),
            'month'           => $payroll->month,
            'year'            => $payroll->year,
            'total'           => $payroll->total_salary ?? 0,
            'deductions'      => ($payroll->insurance ?? 0) + ($payroll->tax ?? 0),
            'net'             => $net,
            'payment_method'  => $employee->bank_account_number ? 'bank_transfer' : 'cash',
            'bank'            => $employee->bank_name,
            'account_holder'  => $employee->bank_account_holder,
            'account_number'  => $employee->bank_account_number,
            'status'          => 'pending',
            'notes'           => $notes,
            'reconciliation_status' => 'unreconciled',
        ]);

        $this->recordLog($payment, 'created', 'Tạo phiếu thanh toán từ bảng lương #' . $payroll->id);

        return $payment;
    }

    public function processPayment(SalaryPayment $payment, array $data): SalaryPayment
    {
        return DB::transaction(function () use ($payment, $data) {
            $payment->update([
                'payment_method'   => $data['payment_method'] ?? $payment->payment_method,
                'bank'             => $data['bank'] ?? $payment->bank,
                'account_holder'   => $data['account_holder'] ?? $payment->account_holder,
                'account_number'   => $data['account_number'] ?? $payment->account_number,
                'transaction_code' => $data['transaction_code'] ?? $payment->transaction_code,
                'cash_payer'       => $data['cash_payer'] ?? $payment->cash_payer,
                'notes'            => $data['notes'] ?? $payment->notes,
                'status'           => 'paid',
                'paid_by'          => Auth::id(),
                'paid_at'          => now(),
            ]);

            if ($payment->payroll_id) {
                Payroll::where('id', $payment->payroll_id)->update([
                    'status'  => 'paid',
                    'paid_at' => now(),
                ]);
            }

            $this->recordLog($payment, 'paid', 'Thanh toán thành công');

            return $payment->fresh();
        });
    }

    public function reconcile(SalaryPayment $payment, ?string $notes = null): SalaryPayment
    {
        $payment->update([
            'reconciliation_status' => 'reconciled',
            'reconciliation_notes'  => $notes,
            'reconciled_at'         => now(),
            'reconciled_by'         => Auth::id(),
        ]);

        $this->recordLog($payment, 'reconciled', 'Đã đối soát thanh toán');

        return $payment->fresh();
    }

    public function markDiscrepancy(SalaryPayment $payment, string $notes): SalaryPayment
    {
        $payment->update([
            'reconciliation_status' => 'discrepancy',
            'reconciliation_notes'  => $notes,
            'reconciled_at'         => now(),
            'reconciled_by'         => Auth::id(),
        ]);

        $this->recordLog($payment, 'discrepancy', 'Phát hiện sai lệch: ' . $notes);

        return $payment->fresh();
    }

    public function createBatch(array $payrollIds, ?string $name = null, ?int $month = null, ?int $year = null): SalaryPaymentBatch
    {
        return DB::transaction(function () use ($payrollIds, $name, $month, $year) {
            $payrolls = Payroll::whereIn('id', $payrollIds)->get();
            $resolvedMonth = $month ?? $payrolls->first()->month;
            $resolvedYear  = $year  ?? $payrolls->first()->year;

            $batch = SalaryPaymentBatch::create([
                'code'            => SalaryPaymentBatch::generateCode(),
                'name'            => $name ?? 'Lương tháng ' . $resolvedMonth . '/' . $resolvedYear,
                'month'           => $resolvedMonth,
                'year'            => $resolvedYear,
                'total_items'     => $payrolls->count(),
                'total_amount'    => $payrolls->sum('total_salary'),
                'total_paid'      => 0,
                'total_remaining' => $payrolls->sum('total_salary'),
                'status'          => 'pending',
                'created_by'      => Auth::id(),
            ]);

            foreach ($payrolls as $payroll) {
                $payment = $this->createPaymentFromPayroll($payroll);
                $payment->update(['batch_id' => $batch->id]);
            }

            return $batch->fresh();
        });
    }

    public function processBatch(SalaryPaymentBatch $batch, array $globalData = []): SalaryPaymentBatch
    {
        return DB::transaction(function () use ($batch, $globalData) {
            $batch->update([
                'status'       => 'processing',
                'approved_by'  => Auth::id(),
                'approved_at'  => now(),
                'processed_at' => now(),
            ]);

            $pendingPayments = $batch->payments()->where('status', 'pending')->get();

            foreach ($pendingPayments as $payment) {
                $this->processPayment($payment, $globalData);
            }

            $totalPaidAll  = $batch->payments()->where('status', 'paid')->sum('net');
            $totalAmount   = $batch->total_amount;

            $finalStatus = 'completed';
            if ($totalPaidAll < $totalAmount && $totalPaidAll > 0) {
                $finalStatus = 'partial';
            } elseif ($totalPaidAll == 0) {
                $finalStatus = 'failed';
            }

            $batch->update([
                'status'          => $finalStatus,
                'total_paid'      => $totalPaidAll,
                'total_remaining' => max(0, $totalAmount - $totalPaidAll),
                'completed_at'    => $finalStatus === 'completed' ? now() : null,
            ]);

            return $batch->fresh();
        });
    }

    public function generateQrCode(SalaryPayment $payment): string
    {
        $bankBin   = $this->getBankBin($payment->bank ?? 'Vietcombank');
        $accountNo = $payment->account_number ?? '';
        $amount    = (int) $payment->net;
        $addInfo   = $payment->code . ' Luong ' . str_pad($payment->month, 2, '0', STR_PAD_LEFT) . '/' . $payment->year;

        $bankBinLen = strlen($bankBin);
        $accountLen = strlen($accountNo);
        $amountLen  = strlen((string) $amount);
        $addInfoLen = strlen($addInfo);

        $content = "0002010102123800010A000000{$bankBinLen}" . $bankBin
            . "0101{$accountLen}" . $accountNo
            . "0200030406{$addInfoLen}" . $addInfo
            . "04{$amountLen}" . $amount
            . "5303704"
            . "54{$amountLen}" . $amount
            . "5802VN"
            . "6304";

        $crc = $this->calculateCRC($content);

        return $content . strtoupper(dechex($crc));
    }

    public function generateQrSvg(string $data): string
    {
        try {
            $encoder  = new Encoder();
            $renderer = new ImageRenderer(
                new RendererStyle(200),
                new SvgImageBackEnd()
            );
            $writer = new Writer($renderer);
            return $writer->writeString($data);
        } catch (\Exception $e) {
            return '<span class="text-danger">QR generation failed</span>';
        }
    }

    public function getStats(?int $month = null, ?int $year = null): array
    {
        $month = $month ?? (int) now()->format('m');
        $year  = $year  ?? (int) now()->format('Y');

        $query = SalaryPayment::where('month', $month)->where('year', $year);

        $total      = (clone $query)->count();
        $pending    = (clone $query)->where('status', 'pending')->count();
        $paid       = (clone $query)->where('status', 'paid')->count();
        $totalNet   = (clone $query)->sum('net');
        $paidNet    = (clone $query)->where('status', 'paid')->sum('net');
        $pendingNet = (clone $query)->where('status', 'pending')->sum('net');

        $reconciled   = (clone $query)->where('reconciliation_status', 'reconciled')->count();
        $discrepancy  = (clone $query)->where('reconciliation_status', 'discrepancy')->count();
        $unreconciled = (clone $query)->where('reconciliation_status', 'unreconciled')->count();

        $bankTransfer = (clone $query)->where('payment_method', 'bank_transfer')->count();
        $cash         = (clone $query)->where('payment_method', 'cash')->count();

        return compact(
            'total', 'pending', 'paid',
            'totalNet', 'paidNet', 'pendingNet',
            'reconciled', 'discrepancy', 'unreconciled',
            'bankTransfer', 'cash',
            'month', 'year'
        );
    }

    public function exportCsv(?int $month = null, ?int $year = null): string
    {
        $payments = SalaryPayment::with('employee', 'payroll', 'paidBy')
            ->when($month, fn($q) => $q->where('month', $month))
            ->when($year, fn($q) => $q->where('year', $year))
            ->orderBy('employee_id')
            ->get();

        $headers = [
            'Mã phiếu', 'Nhân viên', 'Phòng ban', 'Tháng/Năm',
            'Tổng', 'Khấu trừ', 'Thực nhận', 'Phương thức',
            'Ngân hàng', 'STK', 'Chủ TK', 'Trạng thái',
            'Ngày trả', 'Mã GD', 'Đối soát', 'Ghi chú'
        ];

        $csv = implode(';', $headers) . "\n";

        foreach ($payments as $p) {
            $row = [
                $p->code,
                $p->employee->name ?? '',
                $p->employee->department->name ?? '',
                str_pad($p->month, 2, '0', STR_PAD_LEFT) . '/' . $p->year,
                $p->total,
                $p->deductions,
                $p->net,
                $p->payment_method === 'bank_transfer' ? 'CK' : 'TM',
                $p->bank ?? '',
                $p->account_number ?? '',
                $p->account_holder ?? '',
                match($p->status) { 'paid' => 'Đã trả', 'pending' => 'Chờ', default => $p->status },
                $p->paid_at ? $p->paid_at->format('d/m/Y H:i') : '',
                $p->transaction_code ?? '',
                match($p->reconciliation_status) { 'reconciled' => 'OK', 'discrepancy' => 'Sai lệch', default => 'Chưa KS' },
                $p->notes ?? '',
            ];
            $csv .= implode(';', $row) . "\n";
        }

        return $csv;
    }

    public function recordLog(SalaryPayment $payment, string $action, ?string $notes = null): SalaryPaymentLog
    {
        return SalaryPaymentLog::create([
            'salary_payment_id' => $payment->id,
            'user_id'           => Auth::id(),
            'action'            => $action,
            'ip'                => request()->ip(),
            'device'            => request()->userAgent(),
            'notes'             => $notes,
        ]);
    }

    private function getBankBin(string $bankName): string
    {
        return match(strtolower($bankName)) {
            'vietcombank' => '970436',
            'techcombank' => '970407',
            'mb bank'     => '970422',
            'bidv'        => '970418',
            'vietinbank'  => '970415',
            'agribank'    => '970405',
            'acb'         => '970416',
            'tpbank'      => '970406',
            'vpbank'      => '970428',
            'sacombank'   => '970403',
            default       => '970436',
        };
    }

    private function calculateCRC(string $data): int
    {
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= ord($data[$i]) << 8;
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = ($crc << 1) ^ 0x1021;
                } else {
                    $crc <<= 1;
                }
                $crc &= 0xFFFF;
            }
        }
        return $crc;
    }
}
