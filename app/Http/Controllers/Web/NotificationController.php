<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $notifications = Notification::with('sender')
            ->where(function ($query) use ($user) {
                $query->where('target', 'all');

                if ($user->is_director && ! $user->is_hr) {
                    $query->orWhere('target', 'director');
                } elseif ($user->is_hr) {
                    $query->orWhere('target', 'hr');
                } elseif ($user->is_admin) {
                    $query->orWhere('target', 'admin');
                }
            })
            ->latest()
            ->paginate(10);

        return view('notifications.index', compact('notifications', 'user'));
    }

    public function adminIndex(): View
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $user = Auth::user();
        $notifications = Notification::with('sender')
            ->where(function ($query) {
                $query->where('target', 'all')->orWhere('target', 'admin');
            })
            ->latest()
            ->paginate(10);

        return view('notifications.index', compact('notifications', 'user'));
    }

    public function create(): View
    {
        $user = Auth::user();

        return view('notifications.form', compact('user'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'target' => ['required', 'in:employee,hr,all'],
        ]);

        if ($user->is_hr && $data['target'] !== 'employee') {
            abort(403, 'HR chỉ có thể gửi thông báo đến nhân viên.');
        }

        Notification::create([
            'sender_id' => $user->id,
            'title' => $data['title'],
            'message' => $data['message'],
            'target' => $data['target'],
        ]);

        return redirect()->route('notifications.index')->with('success', 'Đã gửi thông báo thành công.');
    }
}
