<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\ApiController;
use App\Models\Contract;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContractController extends ApiController
{
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = Contract::with('employee');

        if ($search = $request->query('q')) {
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('employee', function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = min((int) $request->query('per_page', 10), 50);

        return response()->json($query->paginate($perPage));
    }

    public function show($id): \Illuminate\Http\JsonResponse
    {
        $contract = Contract::with('employee')->findOrFail($id);
        return response()->json($contract);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->requireHr($request);

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'title' => 'required|string|max:255',
            'salary' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $contract = Contract::create(array_merge($validator->validated(), [
            'status' => Contract::STATUS_WAITING_EMPLOYEE_SIGNATURE,
        ]));

        return response()->json($contract, 201);
    }

    public function update(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $this->requireHr($request);

        $contract = Contract::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'employee_id' => 'sometimes|required|exists:employees,id',
            'title' => 'sometimes|required|string|max:255',
            'salary' => 'sometimes|required|numeric|min:0',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = collect($validator->validated())->except([
            'status',
            'contract_status',
            'employee_signed_at',
            'director_signed_at',
            'signed_employee_at',
            'signed_director_at',
        ])->all();
        $contract->update($data);

        return response()->json($contract);
    }

    public function destroy(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $this->requireHr($request);

        $contract = Contract::findOrFail($id);
        $contract->delete();

        return response()->json(null, 204);
    }
}
