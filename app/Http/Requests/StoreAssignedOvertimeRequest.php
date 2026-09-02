<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssignedOvertimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) auth()->user()?->is_hr;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'reason' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.after_or_equal' => 'Không chỉ định tăng ca trong quá khứ.',
            'end_time.after' => 'Giờ kết thúc phải sau giờ bắt đầu.',
            'reason.required' => 'Vui lòng nhập lý do chỉ định tăng ca.',
        ];
    }
}
