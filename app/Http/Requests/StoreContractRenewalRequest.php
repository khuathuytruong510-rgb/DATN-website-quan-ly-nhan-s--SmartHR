<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractRenewalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_hr;
    }

    public function rules(): array
    {
        $parent = $this->route('contract');
        $endRules = ['nullable', 'date', 'after_or_equal:start_date'];
        if ($parent?->end_date) {
            $endRules[0] = 'required';
        }

        return [
            'start_date' => ['required', 'date'],
            'end_date' => $endRules,
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.required' => 'Ngày bắt đầu hợp đồng gia hạn là bắt buộc.',
            'end_date.required' => 'Ngày kết thúc hợp đồng gia hạn là bắt buộc.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.',
        ];
    }
}
