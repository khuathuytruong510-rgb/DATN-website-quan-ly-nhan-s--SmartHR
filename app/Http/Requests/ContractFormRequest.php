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
            'title' => ['nullable', 'string', 'max:255'],
            'contract_code' => ['nullable', 'string', 'max:50', Rule::unique('contracts', 'contract_code')->ignore($contractId)],
            'contract_type' => ['required', 'in:probation,fixed_term,indefinite,internship,consultant,official,seasonal'],
            'sign_date' => ['nullable', 'date'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', 'in:draft,waiting_employee_signature,waiting_director_signature,pending_signature,director_signed,employee_signed,signed,active,expiring,expired,rejected,cancelled,terminated'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'allowance' => ['nullable', 'numeric', 'min:0'],
            'bonus' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'in:bank_transfer,cash'],
            'terms' => ['nullable', 'string'],
            'additional_terms' => ['nullable', 'string'],
            'contract_content' => ['nullable', 'string'],
            'contract_template_id' => ['nullable', 'exists:contract_templates,id'],
            'workplace' => ['nullable', 'string', 'max:255'],
            'working_schedule' => ['nullable', 'string', Rule::in(['morning','evening','morning_evening'])],
            'benefits' => ['nullable', 'string'],
            'signer_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
            'document' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
            'parent_contract_id' => ['nullable', 'exists:contracts,id'],
            'allowed_unpaid_leave_days_per_month' => ['nullable', 'integer', 'min:0', 'max:31'],
            'allowed_makeup_attendance_per_month' => ['nullable', 'integer', 'min:0', 'max:31'],
            'allowed_maternity_leave_days' => ['nullable', 'integer', 'min:0', 'max:365'],
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
