<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\DirectorSuccessionRequest;
use App\Models\ActivityLog;
use App\Models\Position;
use App\Models\User;
use App\Services\DirectorSuccessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;

class DirectorSuccessionController extends Controller
{
    public function __construct(private DirectorSuccessionService $succession)
    {
    }

    public function index(): View
    {
        $directors = $this->succession->currentDirectors();
        $tenures = [];
        $primary = $directors->first();
        if ($primary) {
            $tenures[$primary->id] = $this->succession->ensureOpenTenureFor($primary);
        }

        $candidates = User::query()
            ->where('is_admin', false)
            ->where('is_director', false)
            ->whereHas('employee')
            ->with('employee.department')
            ->orderBy('name')
            ->get();

        return view('director_succession.index', [
            'currentDirectors' => $directors,
            'tenures' => $tenures,
            'minEffectiveOn' => $this->succession->earliestEffectiveOn(),
            'histories' => $this->succession->directorHistories(),
            'candidates' => $candidates,
            'unlinkedEmployees' => $this->succession->unlinkedIncomingProfiles(),
            'positions' => Position::query()->orderBy('name')->get(),
            'logs' => ActivityLog::query()
                ->where('action', 'director_succession')
                ->with('user')
                ->latest()
                ->limit(20)
                ->get(),
        ]);
    }

    public function prepareNew(): View
    {
        return view('director_succession.prepare_new', [
            'hrCreateUrl' => route('employees.create', ['for_director' => 1]),
        ]);
    }

    public function store(DirectorSuccessionRequest $request): RedirectResponse
    {
        $incoming = User::query()->with('employee')->findOrFail($request->integer('incoming_user_id'));

        try {
            $this->succession->appoint($incoming, $request->user(), $request->validated());
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('director_succession.index')
            ->with('success', 'Đã cập nhật người giữ chức Giám đốc. Quyền Director đã chuyển theo người mới; lịch sử phê duyệt cũ được giữ nguyên.');
    }
}
