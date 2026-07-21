<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SalaryPayment;
use App\Models\SalaryPaymentBatch;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentCenterController extends Controller
{
    public function __construct(protected PaymentService $paymentService)
    {
    }

    public function dashboard(Request $request)
    {
        $month = $request->integer('month', (int) now()->format('m'));
        $year  = $request->integer('year', (int) now()->format('Y'));

        $stats = $this->paymentService->getStats($month, $year);

        $recentPayments = SalaryPayment::with('employee', 'paidBy')
            ->where('month', $month)
            ->where('year', $year)
            ->orderByDesc('paid_at')
            ->limit(10)
            ->get();

        $recentBatches = SalaryPaymentBatch::with('createdBy', 'approvedBy')
            ->where('month', $month)
            ->where('year', $year)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $pendingPayments = SalaryPayment::with('employee')
            ->where('month', $month)
            ->where('year', $year)
            ->where('status', 'pending')
            ->orderBy('employee_id')
            ->get();

        return view('payment_center.dashboard', compact(
            'stats', 'recentPayments', 'recentBatches', 'pendingPayments', 'month', 'year'
        ));
    }

    public function bankAccounts(Request $request)
    {
        $search = $request->input('search');
        $departmentId = $request->input('department_id');

        $employees = Employee::with('department')
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('payment_center.bank_accounts', compact('employees'));
    }

    public function updateBankAccount(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'bank_name'          => 'nullable|string|max:100',
            'bank_account_number'=> 'nullable|string|max:50',
            'bank_account_holder'=> 'nullable|string|max:200',
        ]);

        // Ghi vào cột account_* (workflow lương) — alias bank_account_* vẫn tương thích view main
        $employee->update([
            'bank_name' => $validated['bank_name'] ?? $employee->bank_name,
            'account_number' => $validated['bank_account_number'] ?? $employee->account_number,
            'account_holder' => $validated['bank_account_holder'] ?? $employee->account_holder,
        ]);

        return back()->with('success', 'Cập nhật thông tin ngân hàng thành công.');
    }

    public function paymentHistory(Request $request)
    {
        $query = SalaryPayment::with('employee', 'paidBy', 'batch')
            ->orderByDesc('paid_at');

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('reconciliation')) {
            $query->where('reconciliation_status', $request->reconciliation);
        }
        if ($request->filled('employee')) {
            $query->whereHas('employee', fn($q) => $q->where('name', 'like', "%{$request->employee}%"));
        }
        if ($request->filled('department_id')) {
            $query->whereHas('employee', fn($q) => $q->where('department_id', $request->department_id));
        }

        $payments = $query->paginate(25)->withQueryString();

        return view('payment_center.history', compact('payments'));
    }

    public function showPayment(SalaryPayment $salaryPayment)
    {
        $salaryPayment->load('employee', 'payroll', 'paidBy', 'batch', 'reconciledBy', 'logs.user');
        return view('payment_center.show_payment', compact('salaryPayment'));
    }

    public function batchIndex(Request $request)
    {
        $query = SalaryPaymentBatch::with('createdBy', 'approvedBy')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $batches = $query->paginate(20)->withQueryString();
        return view('payment_center.batches', compact('batches'));
    }

    public function batchCreate()
    {
        $payrolls = \App\Models\Payroll::with('employee')
            ->where('status', 'approved')
            ->whereDoesntHave('salaryPayment')
            ->orderBy('month', 'desc')
            ->orderBy('year', 'desc')
            ->get();

        return view('payment_center.batch_create', compact('payrolls'));
    }

    public function batchStore(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'nullable|string|max:255',
            'payroll_ids' => 'required|array|min:1',
            'payroll_ids.*' => 'exists:payrolls,id',
        ]);

        $batch = $this->paymentService->createBatch(
            $validated['payroll_ids'],
            $validated['name'] ?? null
        );

        return redirect()->route('payment_center.batches.show', $batch)
            ->with('success', 'Tạo batch thanh toán thành công. ID: ' . $batch->code);
    }

    public function batchShow(SalaryPaymentBatch $batch)
    {
        $batch->load('createdBy', 'approvedBy', 'payments.employee');
        return view('payment_center.batch_show', compact('batch'));
    }

    public function batchProcess(Request $request, SalaryPaymentBatch $batch)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:bank_transfer,cash',
            'bank'           => 'nullable|string|max:100',
            'notes'          => 'nullable|string|max:500',
        ]);

        $batch = $this->paymentService->processBatch($batch, $validated);

        return back()->with('success', 'Xử lý batch thanh toán hoàn tất. Trạng thái: ' . $batch->status);
    }

    public function batchDestroy(SalaryPaymentBatch $batch)
    {
        if ($batch->status !== 'pending') {
            return back()->with('error', 'Chỉ có batch chưa xử lý mới được xóa.');
        }

        DB::transaction(function () use ($batch) {
            $batch->payments()->update(['batch_id' => null]);
            $batch->delete();
        });

        return redirect()->route('payment_center.batches.index')
            ->with('success', 'Đã xóa batch thanh toán.');
    }

    public function qrCode(SalaryPayment $salaryPayment)
    {
        $salaryPayment->load('employee');

        $qrData = $this->paymentService->generateQrCode($salaryPayment);
        $qrSvg  = $this->paymentService->generateQrSvg($qrData);

        $salaryPayment->update(['qr_code' => $qrData]);

        return view('payment_center.qr_code', compact('salaryPayment', 'qrSvg', 'qrData'));
    }

    public function reconcileIndex(Request $request)
    {
        $query = SalaryPayment::with('employee', 'reconciledBy')
            ->where('status', 'paid')
            ->orderByDesc('paid_at');

        if ($request->filled('reconciliation_status')) {
            $query->where('reconciliation_status', $request->reconciliation_status);
        } else {
            $query->where('reconciliation_status', '!=', 'reconciled');
        }

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        $payments = $query->paginate(25)->withQueryString();
        return view('payment_center.reconcile', compact('payments'));
    }

    public function reconcileStore(Request $request, SalaryPayment $salaryPayment)
    {
        $validated = $request->validate([
            'action' => 'required|in:reconcile,discrepancy',
            'notes'  => 'nullable|string|max:500',
        ]);

        if ($validated['action'] === 'reconcile') {
            $this->paymentService->reconcile($salaryPayment, $validated['notes'] ?? null);
            $msg = 'Đã xác nhận đối soát.';
        } else {
            $this->paymentService->markDiscrepancy($salaryPayment, $validated['notes'] ?? 'Phát hiện sai lệch');
            $msg = 'Đã đánh dấu sai lệch.';
        }

        return back()->with('success', $msg);
    }

    public function export(Request $request)
    {
        $month = $request->integer('month');
        $year  = $request->integer('year');

        $csv = $this->paymentService->exportCsv($month, $year);
        $filename = 'thanh_toan_luong';
        if ($month && $year) {
            $filename .= "_{$month}_{$year}";
        }
        $filename .= '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate',
        ]);
    }
}
