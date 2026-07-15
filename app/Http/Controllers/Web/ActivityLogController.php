<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $logs = ActivityLog::where('user_id', $user->id)->latest()->paginate(20);

        return view('employee.activity.index', compact('logs'));
    }
}
