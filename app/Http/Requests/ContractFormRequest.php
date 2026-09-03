<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ContractFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $contractId = $this->route('contract')?->id;

        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'employee_email' => ['required', 'email', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'contract_code' => ['nullable', 'string', 'max:50', Rule::unique('contracts', 'contract_code')->ignore($contractId)],
            'contract_type' => ['required', 'in:probation,fixed_term,indefinite,internship,consultant,official,seasonal'],
            'sign_date' => ['nullable', 'date'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'allowance' => ['nullable', 'numeric', 'min:0'],
            'bonus' => ['nullable', 'numeric', 'min:0'],
            'terms' => ['nullable', 'string'],
            'additional_terms' => ['nullable', 'string'],
            'contract_content' => ['nullable', 'string'],
            'contract_template_id' => ['nullable', 'exists:contract_templates,id'],
            'workplace' => ['nullable', 'string', 'max:255'],
            'benefits' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'document' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
            'parent_contract_id' => ['nullable', 'exists:contracts,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = (string) $this->input('contract_type');
            $endDate = $this->input('end_date');

            $requiresEnd = in_array($type, ['probation', 'fixed_term', 'internship', 'seasonal'], true);
            if ($requiresEnd && blank($endDate)) {
                $validator->errors()->add('end_date', 'Loại hợp đồng này bắt buộc có ngày kết thúc.');
            }

            if ($type === 'indefinite' && filled($endDate)) {
                $validator->errors()->add('end_date', 'Hợp đồng không xác định thời hạn không được có ngày kết thúc.');
            }

            $email = strtolower(trim((string) $this->input('employee_email')));
            $employeeId = (int) $this->input('employee_id');
            if ($email !== '' && $employeeId > 0) {
                $takenByOtherEmployee = \App\Models\Employee::query()
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->where('id', '!=', $employeeId)
                    ->exists();
                if ($takenByOtherEmployee) {
                    $validator->errors()->add('employee_email', 'Email này đã được dùng cho nhân viên khác.');
                }

                $employee = \App\Models\Employee::find($employeeId);
                $linkedUserId = $employee?->user_id;
                $takenByUser = \App\Models\User::query()
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->when($linkedUserId, fn ($q) => $q->where('id', '!=', $linkedUserId))
                    ->exists();
                if ($takenByUser) {
                    $validator->errors()->add('employee_email', 'Email này đã được dùng cho tài khoản đăng nhập khác.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Nhân viên là bắt buộc.',
            'employee_email.required' => 'Email nhân viên là bắt buộc.',
            'employee_email.email' => 'Email nhân viên không hợp lệ.',
            'title.required' => 'Tên hợp đồng là bắt buộc.',
            'contract_type.required' => 'Loại hợp đồng là bắt buộc.',
            'start_date.required' => 'Ngày bắt đầu là bắt buộc.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.',
            'base_salary.required' => 'Lương cơ bản là bắt buộc.',
            'base_salary.min' => 'Lương cơ bản phải lớn hơn hoặc bằng 0.',
            'document.mimes' => 'Chỉ cho phép upload file PDF hoặc DOCX.',
        ];
    }
}
