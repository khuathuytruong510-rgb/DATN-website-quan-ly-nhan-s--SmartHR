<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'title' => ['required', 'string', 'max:255'],
            'contract_code' => ['nullable', 'string', 'max:50', Rule::unique('contracts', 'contract_code')->ignore($contractId)],
            'contract_type' => ['required', 'in:probation,fixed_term,indefinite,consultant'],
            'sign_date' => ['nullable', 'date'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', 'in:waiting_employee,waiting_director,active,expiring,expired,cancelled'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'allowance' => ['nullable', 'numeric', 'min:0'],
            'bonus' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'in:bank_transfer,cash'],
            'terms' => ['nullable', 'string'],
            'signer_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
            'document' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
            'parent_contract_id' => ['nullable', 'exists:contracts,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Nhân viên là bắt buộc.',
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
