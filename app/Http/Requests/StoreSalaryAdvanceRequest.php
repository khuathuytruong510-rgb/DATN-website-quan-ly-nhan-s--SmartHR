<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalaryAdvanceRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'amount' => 'required|numeric|min:1',
            'reason' => 'nullable|string|max:2000',
            'requested_at' => 'required|date',
        ];
    }
}
