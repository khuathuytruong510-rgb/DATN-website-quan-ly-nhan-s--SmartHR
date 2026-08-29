<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class ApiController extends BaseController
{
    protected function requireHr(Request $request): User
    {
        $user = $this->currentUser($request);
        if (! $user->is_hr) {
            abort(403, 'Chỉ HR được ghi dữ liệu nhân sự qua API.');
        }

        return $user;
    }

    protected function currentUser(Request $request): User
    {
        $token = $request->bearerToken();

        if (! $token) {
            abort(401, 'Unauthorized');
        }

        $user = User::where('api_token', $token)->first();

        if (! $user) {
            abort(401, 'Unauthorized');
        }

        return $user;
    }
}
