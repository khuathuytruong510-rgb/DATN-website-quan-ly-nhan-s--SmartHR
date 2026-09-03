<?php

namespace App\Http\Requests;

use App\Services\DirectorSuccessionService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DirectorSuccessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        return [
            'incoming_user_id' => ['required', 'integer', 'exists:users,id'],
            'effective_on' => ['required', 'date'],
            'outgoing_role' => ['required', Rule::in(['employee', 'hr', 'accountant'])],
            'outgoing_status' => ['required', Rule::in([
                DirectorSuccessionService::OUTGOING_WORKING,
                DirectorSuccessionService::OUTGOING_RESIGNED,
                DirectorSuccessionService::OUTGOING_ON_LEAVE,
            ])],
            'outgoing_position' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(function () {
                    return $this->input('outgoing_status') === DirectorSuccessionService::OUTGOING_WORKING
                        && app(DirectorSuccessionService::class)->currentDirector() !== null;
                }),
            ],
            'decision_ref' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'incoming_user_id.required' => 'Chọn người được bổ nhiệm làm Giám đốc.',
            'effective_on.required' => 'Nhập ngày hiệu lực theo quyết định của doanh nghiệp.',
            'outgoing_position.required' => 'Nhập chức vụ mới của người cũ vì hồ sơ vẫn còn làm việc.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $effective = $this->input('effective_on');
            if (! $effective) {
                return;
            }

            $min = app(DirectorSuccessionService::class)->earliestEffectiveOn();
            if ($min && Carbon::parse($effective)->startOfDay()->lt(Carbon::parse($min)->startOfDay())) {
                $validator->errors()->add(
                    'effective_on',
                    'Ngày hiệu lực không được sớm hơn hoặc chồng lên nhiệm kỳ Giám đốc hiện tại. Ngày sớm nhất: '
                    .Carbon::parse($min)->format('d/m/Y').'.'
                );
            }

            $incomingId = (int) $this->input('incoming_user_id');
            if ($incomingId > 0) {
                $incoming = \App\Models\User::query()->with('employee')->find($incomingId);
                $employee = $incoming?->linkedEmployee();
                if ($incoming && ($incoming->is_locked || ($employee && $employee->isTerminated()))) {
                    $validator->errors()->add(
                        'incoming_user_id',
                        'Không chọn nhân viên đã nghỉ việc hoặc tài khoản đang khóa.'
                    );
                }
            }
        });
    }
}
