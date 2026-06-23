<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\ApiController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends ApiController
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'api_token' => Str::random(60),
            'role' => $request->role ?? 'employee',
            'is_active' => true,
            'department' => $request->department,
            'position' => $request->position,
        ]);

        return response()->json([
            'user' => $user,
            'token' => $user->api_token
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Thiếu thông tin đăng nhập'
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email hoặc mật khẩu không đúng'
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Tài khoản đã bị vô hiệu hóa'
            ], 403);
        }

        $user->api_token = Str::random(60);
        $user->save();

        return response()->json([
            'user' => $user,
            'token' => $user->api_token
        ]);
    }

    public function logout(Request $request)
    {
        $user = $this->currentUser($request);

        $user->api_token = null;
        $user->save();

        return response()->json([
            'message' => 'Đăng xuất thành công'
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = $this->currentUser($request);

        if (!Hash::check(
            $request->old_password,
            $user->password
        )) {
            return response()->json([
                'message' => 'Mật khẩu cũ không đúng'
            ], 400);
        }

        $user->password = Hash::make(
            $request->new_password
        );

        $user->save();

        return response()->json([
            'message' => 'Đổi mật khẩu thành công'
        ]);
    }
}