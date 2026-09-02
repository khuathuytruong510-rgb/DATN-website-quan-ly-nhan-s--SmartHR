<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Services\ContractDocumentService;
use App\Services\ContractService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ContractEsignController extends Controller
{
    public function document(Contract $contract, ContractDocumentService $documents): View
    {
        $this->authorizeView($contract);
        $contract->loadMissing(['employee.department', 'directorSignature.signer', 'employeeSignature.signer']);

        $payload = $documents->canonicalPayload($contract);
        if ($contract->canonical_document_path && Storage::disk('local')->exists($contract->canonical_document_path)) {
            $stored = json_decode(Storage::disk('local')->get($contract->canonical_document_path), true);
            if (is_array($stored)) {
                $payload = $stored;
            }
        }

        $service = app(ContractService::class);
        $valid = $contract->isFullySigned()
            ? $service->verifyAllSignatures($contract)
            : ($contract->director_signed_at
                ? $service->verifyDirectorSignature($contract)
                : $documents->matchesFrozenHash($contract));

        return view('contracts.document', [
            'contract' => $contract,
            'payload' => $payload,
            'hashValid' => $valid,
            'disclaimer' => config('esign.disclaimer'),
        ]);
    }

    public function sendForSignature(Request $request, Contract $contract, ContractService $service): RedirectResponse
    {
        if (! $request->user()?->is_hr) {
            abort(403, 'Chỉ HR được gửi hợp đồng cho Giám đốc ký số.');
        }

        try {
            $service->sendForDirectorSignature($request->user(), $contract);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('contracts.show', $contract)
            ->with('success', 'Đã khóa tài liệu và gửi chờ ký. Giám đốc ký trước, sau đó nhân viên ký. Nội dung không còn sửa được trên bản này.');
    }

    public function reject(Request $request, Contract $contract, ContractService $service): RedirectResponse
    {
        if (! $request->user()?->is_director) {
            abort(403, 'Chỉ Giám đốc được từ chối ký số.');
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:8', 'max:500'],
        ]);

        try {
            $service->rejectDirectorSignature($request->user(), $contract, $data['reason']);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('contracts.show', $contract)
            ->with('success', 'Đã từ chối ký số. HR có thể chỉnh nội dung và gửi ký lại.');
    }

    protected function authorizeView(Contract $contract): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }
        if ($user->is_hr || $user->is_director || $user->is_admin || $user->is_accountant) {
            return;
        }
        $employee = $user->linkedEmployee();
        if ($employee && (int) $employee->id === (int) $contract->employee_id) {
            return;
        }
        abort(403, 'Bạn không được xem tài liệu hợp đồng này.');
    }
}
