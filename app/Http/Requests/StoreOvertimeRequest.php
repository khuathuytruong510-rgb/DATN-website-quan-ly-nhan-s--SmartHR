<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOvertimeRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->request->remove('actual_minutes');
        $this->request->remove('actual_start');
        $this->request->remove('actual_end');
        $this->request->remove('status');
        $this->request->remove('approved_by');
        $this->request->remove('approved_at');
        $this->request->remove('approved_start');
        $this->request->remove('approved_end');
        $this->request->remove('employee_id');
        $this->request->remove('source');
        $this->request->remove('assigned_by');
        $this->request->remove('verified_by');
        $this->request->remove('verified_at');
    }

    public function rules()
    {
        return [
            'date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:tomorrow'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'reason' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.after_or_equal' => 'Không được chọn ngày tăng ca trong quá khứ.',
            'date.before_or_equal' => 'Nhân viên chỉ được đăng ký tăng ca hôm nay hoặc ngày mai.',
            'end_time.after' => 'Giờ kết thúc phải sau giờ bắt đầu.',
            'reason.required' => 'Vui lòng nhập lý do / công việc tăng ca.',
        ];
    }
}
