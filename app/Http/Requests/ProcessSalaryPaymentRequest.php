<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessSalaryPaymentRequest extends FormRequest
{
    public function authorize()
    {
        $user = auth()->user();

        return $user !== null && $user->canPayPayroll();
    }

    public function rules()
    {
        return [
            'payment_method' => 'required|in:cash,bank',
            'bank' => 'nullable|string|max:255',
            'account_holder' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'transaction_code' => 'nullable|string|max:255',
            'cash_payer' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ];
    }
}
