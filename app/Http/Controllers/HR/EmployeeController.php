<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\ApiController;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends ApiController
{
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = Employee::with('department');

        if ($search = $request->query('q')) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhereHas('department', function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = min((int) $request->query('per_page', 10), 50);

        return response()->json($query->paginate($perPage));
    }

    public function show($id): \Illuminate\Http\JsonResponse
    {
        $employee = Employee::with('department')->findOrFail($id);
        return response()->json($employee);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->currentUser($request);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'position' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = Employee::create(array_merge($validator->validated(), ['status' => $request->input('status', 'active')]));

        $this->syncDepartmentCount($employee->department_id);

        return response()->json($employee, 201);
    }

    public function update(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $this->currentUser($request);

        $employee = Employee::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:employees,email,'.$employee->id,
            'position' => 'sometimes|required|string|max:255',
            'department_id' => 'sometimes|required|exists:departments,id',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $employee->update($data);

        if (isset($data['department_id'])) {
            $this->syncDepartmentCount($data['department_id']);
        }

        return response()->json($employee);
    }

    public function destroy(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $this->currentUser($request);

        $employee = Employee::findOrFail($id);
        $departmentId = $employee->department_id;
        $employee->delete();

        $this->syncDepartmentCount($departmentId);

        return response()->json(null, 204);
    }

    protected function syncDepartmentCount(int $departmentId): void
    {
        $department = Department::find($departmentId);

        if ($department) {
            $department->employee_count = Employee::where('department_id', $departmentId)->count();
            $department->save();
        }
    }
}
