<?php

namespace App\Services;

use App\Models\Contract;
use Illuminate\Support\Facades\Storage;

class ContractDocumentService
{
    public function canonicalPayload(Contract $contract): array
    {
        $contract->loadMissing('employee.department');

        return [
            'contract_code' => (string) $contract->contract_code,
            'contract_type' => (string) $contract->contract_type,
            'title' => (string) $contract->title,
            'employee_id' => (int) $contract->employee_id,
            'employee_name' => (string) optional($contract->employee)->name,
            'employee_code' => (string) optional($contract->employee)->employee_code,
            'start_date' => optional($contract->start_date)?->toDateString(),
            'end_date' => optional($contract->end_date)?->toDateString(),
            'base_salary' => (string) $contract->base_salary,
            'allowance' => (string) $contract->allowance,
            'workplace' => (string) $contract->workplace,
            'working_schedule' => (string) $contract->working_schedule,
            'terms' => (string) ($contract->contract_content ?: $contract->terms),
            'additional_terms' => (string) $contract->additional_terms,
        ];
    }

    public function hashPayload(array $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return hash('sha256', (string) $json);
    }

    public function currentHash(Contract $contract): string
    {
        return $this->hashPayload($this->canonicalPayload($contract));
    }

    public function freeze(Contract $contract): Contract
    {
        $payload = $this->canonicalPayload($contract);
        $hash = $this->hashPayload($payload);
        $relative = 'contracts/canonical/'.$contract->id.'.json';
        Storage::disk('local')->put($relative, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $contract->forceFill([
            'document_hash' => $hash,
            'canonical_document_path' => $relative,
            'content_locked_at' => $contract->content_locked_at ?? now(),
        ])->save();

        return $contract->fresh();
    }

    public function matchesFrozenHash(Contract $contract): bool
    {
        $stored = (string) $contract->document_hash;
        if ($stored === '') {
            return false;
        }

        return hash_equals($stored, $this->currentHash($contract));
    }
}
