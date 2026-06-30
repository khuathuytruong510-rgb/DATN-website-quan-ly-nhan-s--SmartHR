<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Mail\PayrollMail;
use App\Models\Payroll;
use Illuminate\Support\Facades\Mail;

class PayrollMailController extends Controller
{
    public function send(Payroll $payroll)
    {
        $payroll->load('employee');

        Mail::to($payroll->employee->email)
            ->send(new PayrollMail($payroll));

        return back()->with('success', 'Đã gửi email bảng lương!');
    }
}
