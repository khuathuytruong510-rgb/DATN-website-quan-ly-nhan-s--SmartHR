<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SalaryAdvance;
use App\Models\Employee;
use App\Http\Requests\StoreSalaryAdvanceRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\SalaryAdvanceApprovedMail;

class SalaryAdvanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = SalaryAdvance::with('employee')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->employee, fn($q) => $q->whereHas('employee', fn($qq) => $qq->where('name', 'like', "%{$request->employee}%")));

        $advances = $query->orderBy('requested_at', 'desc')->paginate(20);

        return view('salary_advances.index', compact('advances'));
    }

    public function create()
    {
        return view('salary_advances.create');
    }

    public function store(StoreSalaryAdvanceRequest $request)
    {
        // simple business validations: not exceed company limit or salary
        $employee = Auth::user()->employee ?? Employee::find(Auth::id());

        $amount = $request->input('amount');

        // TODO: replace with company limit config
        $companyLimit = 1000000000;

        if ($amount > $companyLimit) {
            return back()->with('error', 'Số tiền ứng vượt quá hạn mức công ty.');
        }

        // check existing pending
        $pending = SalaryAdvance::where('employee_id', $employee->id)->whereIn('status', ['pending','processing'])->exists();
        if ($pending) {
            return back()->with('error', 'Bạn có yêu cầu ứng đang chờ xử lý.');
        }

        $advance = SalaryAdvance::create([
            'code' => 'ADV-'.time(),
            'employee_id' => $employee->id,
            'amount' => $amount,
            'reason' => $request->input('reason'),
            'requested_at' => $request->input('requested_at'),
            'status' => 'pending',
        ]);

        return redirect()->route('salary_advances.index')->with('success', 'Yêu cầu ứng lương đã được tạo.');
    }

    public function approve(Request $request, SalaryAdvance $salaryAdvance)
    {
        // HR/Director approval - simple role check
        $user = Auth::user();
        if (!($user->hasRole('hr') || $user->hasRole('director') || $user->hasRole('admin'))) {
            abort(403);
        }

        $salaryAdvance->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        // notify employee
        if ($salaryAdvance->employee && !empty($salaryAdvance->employee->email)) {
            try {
                Mail::to($salaryAdvance->employee->email)->send(new SalaryAdvanceApprovedMail($salaryAdvance));
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return back()->with('success', 'Yêu cầu đã được duyệt.');
    }
}
