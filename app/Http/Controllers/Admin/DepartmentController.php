<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends ApiController
{
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = Department::query();

        if ($search = $request->query('q')) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('manager', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->query('per_page', 10), 50);

        return response()->json($query->paginate($perPage));
    }

    public function show($id): \Illuminate\Http\JsonResponse
    {
        $department = Department::findOrFail($id);
        return response()->json($department);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->currentUser($request);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'manager' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $department = Department::create(array_merge($validator->validated(), ['employee_count' => 0]));

        return response()->json($department, 201);
    }

    public function update(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $this->currentUser($request);
        $department = Department::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'manager' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $department->update($validator->validated());

        return response()->json($department);
    }

    public function destroy(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $this->currentUser($request);
        $department = Department::findOrFail($id);
        $department->delete();

        return response()->json(null, 204);
    }
}
