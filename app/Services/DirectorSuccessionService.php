<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeePositionHistory;
use App\Models\Payroll;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DirectorSuccessionService
{
    public const POSITION_NAME = 'Giám đốc';
    public const BOARD_CODE = 'BGD';

    public const OUTGOING_WORKING = 'active';
    public const OUTGOING_RESIGNED = 'resigned';
    public const OUTGOING_ON_LEAVE = 'on_leave';

    public function currentDirectors()
    {
        return User::query()
            ->where('is_director', true)
            ->with('employee.department')
            ->orderBy('id')
            ->get();
    }

    public function currentDirector(): ?User
    {
        return $this->currentDirectors()->first();
    }

    public function currentDirectorTenure(): ?EmployeePositionHistory
    {
        return EmployeePositionHistory::query()
            ->where('is_director_role', true)
            ->where('status', EmployeePositionHistory::STATUS_HOLDING)
            ->whereNull('ended_at')
            ->orderBy('started_at')
            ->orderBy('id')
            ->first();
    }

    /** Ngày hiệu lực sớm nhất: sau ngày bắt đầu nhiệm kỳ đang mở. */
    public function earliestEffectiveOn(): ?string
    {
        $start = $this->currentTenureStart();
        if (! $start) {
            return null;
        }

        return $start->copy()->addDay()->toDateString();
    }

    public function currentTenureStart(): ?Carbon
    {
        $tenure = $this->currentDirectorTenure();
        if ($tenure?->started_at) {
            return Carbon::parse($tenure->started_at)->startOfDay();
        }

        $director = $this->currentDirector();
        if (! $director) {
            return null;
        }

        $employee = $director->linkedEmployee();
        $raw = $employee?->start_date ?: $director->created_at;
        if (! $raw) {
            return null;
        }

        return Carbon::parse($raw)->startOfDay();
    }

    public function unlinkedIncomingProfiles()
    {
        return Employee::query()
            ->where(function ($query) {
                $query->whereNull('user_id')
                    ->orWhereDoesntHave('user');
            })
            ->with('department')
            ->orderBy('name')
            ->limit(30)
            ->get();
    }

    public function directorHistories()
    {
        return EmployeePositionHistory::query()
            ->where('is_director_role', true)
            ->with(['employee', 'user'])
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->get();
    }

    public function ensureOpenTenureFor(User $director): ?EmployeePositionHistory
    {
        $employee = $director->linkedEmployee();
        if (! $employee) {
            return null;
        }

        $open = EmployeePositionHistory::query()
            ->where('employee_id', $employee->id)
            ->where('is_director_role', true)
            ->where('status', EmployeePositionHistory::STATUS_HOLDING)
            ->whereNull('ended_at')
            ->first();

        if ($open) {
            return $open;
        }

        return $this->openTenure(
            $employee,
            $director,
            $this->directorPosition(),
            true,
            Carbon::parse($employee->start_date ?: $director->created_at ?: now()),
            null,
            EmployeePositionHistory::REASON_APPOINTMENT,
            $director
        );
    }

    /**
     * @param  array{effective_on:string, outgoing_role?:string, outgoing_status?:string, outgoing_position?:?string, decision_ref?:?string, note?:?string}  $data
     */
    public function appoint(User $incoming, User $actor, array $data): void
    {
        $effectiveOn = Carbon::parse($data['effective_on'])->startOfDay();
        $outgoingRole = $data['outgoing_role'] ?? 'employee';
        $outgoingStatus = $data['outgoing_status'] ?? self::OUTGOING_WORKING;
        $outgoingPosition = filled($data['outgoing_position'] ?? null) ? trim((string) $data['outgoing_position']) : 'Nhân viên';
        $decisionRef = filled($data['decision_ref'] ?? null) ? trim((string) $data['decision_ref']) : null;
        $note = filled($data['note'] ?? null) ? trim((string) $data['note']) : null;

        $this->assertEffectiveOn($effectiveOn);

        if ($incoming->is_admin) {
            throw new RuntimeException('Admin hệ thống không đồng thời giữ vai trò Giám đốc.');
        }

        $incomingEmployee = $incoming->linkedEmployee();
        if (! $incomingEmployee) {
            throw new RuntimeException('Người được bổ nhiệm phải đã có hồ sơ nhân viên. Không đổi tên tài khoản Giám đốc cũ.');
        }

        $outgoingDirectors = $this->currentDirectors()->reject(fn (User $user) => $user->id === $incoming->id);

        if ($incoming->is_director && $outgoingDirectors->isEmpty()) {
            throw new RuntimeException('Người này đang giữ chức Giám đốc.');
        }

        DB::transaction(function () use (
            $incoming,
            $incomingEmployee,
            $actor,
            $effectiveOn,
            $outgoingDirectors,
            $outgoingRole,
            $outgoingStatus,
            $outgoingPosition,
            $decisionRef,
            $note
        ) {
            $directorPosition = $this->directorPosition();
            $board = $this->boardDepartment();

            foreach ($outgoingDirectors as $outgoing) {
                $this->demoteOutgoing(
                    $outgoing,
                    $actor,
                    $effectiveOn,
                    $outgoingRole,
                    $outgoingStatus,
                    $outgoingPosition,
                    $decisionRef,
                    $note
                );
            }

            $this->closeAllOpenDirectorTenures($effectiveOn, $decisionRef, $note);

            $this->promoteIncoming(
                $incoming,
                $incomingEmployee,
                $actor,
                $effectiveOn,
                $directorPosition,
                $board,
                $decisionRef,
                $note
            );

            User::query()
                ->where('is_director', true)
                ->where('id', '!=', $incoming->id)
                ->get()
                ->each(fn (User $extra) => $extra->update($this->roleFlags('employee')));

            $holding = EmployeePositionHistory::query()
                ->where('is_director_role', true)
                ->where('status', EmployeePositionHistory::STATUS_HOLDING)
                ->whereNull('ended_at')
                ->count();

            $directors = User::query()->where('is_director', true)->count();
            if ($directors !== 1 || $holding !== 1) {
                throw new RuntimeException('Tại một thời điểm chỉ được có 01 người giữ chức Giám đốc.');
            }

            $from = $outgoingDirectors->map(fn (User $user) => $user->name.' ('.$user->email.')')->implode(', ');
            if ($from === '') {
                $from = '—';
            }

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'director_succession',
                'meta' => implode('; ', array_filter([
                    'from_name:'.$from,
                    'to_name:'.$incoming->name.' ('.$incoming->email.')',
                    'effective:'.$effectiveOn->toDateString(),
                    $decisionRef ? 'decision:'.$decisionRef : null,
                    $note ? 'note:'.$note : null,
                ])),
            ]);
        });
    }

    public function assertEffectiveOn(Carbon $effectiveOn): void
    {
        $min = $this->earliestEffectiveOn();
        if ($min && $effectiveOn->lt(Carbon::parse($min)->startOfDay())) {
            throw new RuntimeException(
                'Ngày hiệu lực không được sớm hơn hoặc chồng lên nhiệm kỳ Giám đốc hiện tại. Ngày sớm nhất: '
                .Carbon::parse($min)->format('d/m/Y').'.'
            );
        }
    }

    public function assertMayDeleteAccount(User $user): void
    {
        if ($user->is_director) {
            throw new RuntimeException('Không xóa tài khoản đang giữ vai trò Giám đốc. Hãy cập nhật người giữ chức trước.');
        }

        if ($this->hasDirectorTenure($user, $user->linkedEmployee())) {
            throw new RuntimeException('Không xóa tài khoản Giám đốc cũ. Lịch sử phê duyệt và nhiệm kỳ phải được giữ nguyên.');
        }

        if ($this->hasDirectorApprovals($user)) {
            throw new RuntimeException('Không xóa tài khoản đã phê duyệt bảng lương. Lịch sử người duyệt phải được giữ nguyên.');
        }
    }

    public function assertMayDeleteEmployee(?Employee $employee): void
    {
        if (! $employee) {
            return;
        }

        $user = $employee->user ?: ($employee->user_id ? User::query()->find($employee->user_id) : null);

        if ($user?->is_director || \App\Support\RequestApprover::isDirectorEmployee($employee)) {
            throw new RuntimeException('Không xóa hồ sơ người đang giữ chức Giám đốc.');
        }

        if ($this->hasDirectorTenure($user, $employee)) {
            throw new RuntimeException('Không xóa hồ sơ Giám đốc cũ. Lịch sử nhiệm kỳ và phê duyệt phải được giữ nguyên.');
        }
    }

    public function hasDirectorTenure(?User $user, ?Employee $employee = null): bool
    {
        if (! $user && ! $employee) {
            return false;
        }

        return EmployeePositionHistory::query()
            ->where('is_director_role', true)
            ->where(function ($inner) use ($user, $employee) {
                if ($user) {
                    $inner->where('user_id', $user->id);
                }
                if ($employee) {
                    $method = $user ? 'orWhere' : 'where';
                    $inner->{$method}('employee_id', $employee->id);
                }
            })
            ->exists();
    }

    private function hasDirectorApprovals(User $user): bool
    {
        return Payroll::query()
            ->where(function ($query) use ($user) {
                $query->where('director_approved_by', $user->id)
                    ->orWhere('sent_by', $user->id);
            })
            ->exists();
    }

    private function demoteOutgoing(
        User $outgoing,
        User $actor,
        Carbon $effectiveOn,
        string $outgoingRole,
        string $outgoingStatus,
        string $outgoingPosition,
        ?string $decisionRef,
        ?string $note
    ): void {
        $this->closeDirectorTenures($outgoing, $effectiveOn, $decisionRef, $note);

        $outgoing->update($this->roleFlags($outgoingRole));

        $employee = $outgoing->linkedEmployee();
        if (! $employee) {
            return;
        }

        $employee->update($this->outgoingEmployeePayload($employee, $outgoingStatus, $outgoingPosition));

        if ($outgoingStatus === self::OUTGOING_WORKING) {
            $this->openTenure(
                $employee->fresh(),
                $outgoing,
                $this->findPositionByName($outgoingPosition),
                false,
                $effectiveOn,
                $decisionRef,
                EmployeePositionHistory::REASON_SUCCESSION,
                $actor,
                $note
            );
        }
    }

    private function promoteIncoming(
        User $incoming,
        Employee $employee,
        User $actor,
        Carbon $effectiveOn,
        Position $directorPosition,
        ?Department $board,
        ?string $decisionRef,
        ?string $note
    ): void {
        $this->closeOpenTenures($employee, $effectiveOn, $decisionRef, $note);

        $incoming->update([
            'is_admin' => false,
            'is_director' => true,
            'is_hr' => false,
            'is_accountant' => false,
        ]);

        $payload = [
            'status' => 'active',
            'position' => self::POSITION_NAME,
            'position_id' => $directorPosition->id,
        ];

        if ($board) {
            $payload['department_id'] = $board->id;
            $board->update(['manager' => $incoming->name]);
        }

        $employee->update($payload);

        $this->openTenure(
            $employee->fresh(['department']),
            $incoming,
            $directorPosition,
            true,
            $effectiveOn,
            $decisionRef,
            EmployeePositionHistory::REASON_APPOINTMENT,
            $actor,
            $note
        );
    }

    private function closeDirectorTenures(User $outgoing, Carbon $effectiveOn, ?string $decisionRef, ?string $note): void
    {
        $query = EmployeePositionHistory::query()
            ->where('is_director_role', true)
            ->where('status', EmployeePositionHistory::STATUS_HOLDING)
            ->whereNull('ended_at');

        $employee = $outgoing->linkedEmployee();
        if ($employee) {
            $employee->loadMissing('department');
            $query->where(function ($inner) use ($outgoing, $employee) {
                $inner->where('user_id', $outgoing->id)
                    ->orWhere('employee_id', $employee->id);
            });
        } else {
            $query->where('user_id', $outgoing->id);
        }

        $closed = $query->get();

        if ($closed->isEmpty()) {
            if (! $employee) {
                return;
            }

            $startedAt = Carbon::parse($employee->start_date ?: $outgoing->created_at ?: $effectiveOn)->startOfDay();
            EmployeePositionHistory::create([
                'employee_id' => $employee->id,
                'user_id' => $outgoing->id,
                'holder_name' => $outgoing->name,
                'holder_email' => $outgoing->email,
                'position_id' => $employee->position_id,
                'position_name' => self::POSITION_NAME,
                'department_id' => $employee->department_id,
                'department_name' => optional($employee->department)->name,
                'started_at' => $startedAt->toDateString(),
                'ended_at' => $this->tenureEndDate($startedAt, $effectiveOn),
                'end_reason' => EmployeePositionHistory::REASON_SUCCESSION,
                'is_director_role' => true,
                'status' => EmployeePositionHistory::STATUS_ENDED,
                'decision_ref' => $decisionRef,
                'note' => $note,
                'created_by' => null,
            ]);

            return;
        }

        $closed->each(function (EmployeePositionHistory $row) use ($effectiveOn, $decisionRef, $note) {
            $end = $this->tenureEndDate($row->started_at, $effectiveOn);
            $row->update([
                'ended_at' => $end,
                'status' => EmployeePositionHistory::STATUS_ENDED,
                'end_reason' => EmployeePositionHistory::REASON_SUCCESSION,
                'decision_ref' => $row->decision_ref ?: $decisionRef,
                'note' => $note ?: $row->note,
            ]);
        });
    }

    private function closeAllOpenDirectorTenures(Carbon $effectiveOn, ?string $decisionRef, ?string $note): void
    {
        EmployeePositionHistory::query()
            ->where('is_director_role', true)
            ->where('status', EmployeePositionHistory::STATUS_HOLDING)
            ->whereNull('ended_at')
            ->get()
            ->each(function (EmployeePositionHistory $row) use ($effectiveOn, $decisionRef, $note) {
                $row->update([
                    'ended_at' => $this->tenureEndDate($row->started_at, $effectiveOn),
                    'status' => EmployeePositionHistory::STATUS_ENDED,
                    'end_reason' => EmployeePositionHistory::REASON_SUCCESSION,
                    'decision_ref' => $row->decision_ref ?: $decisionRef,
                    'note' => $note ?: $row->note,
                ]);
            });
    }

    private function outgoingEmployeePayload(Employee $employee, string $outgoingStatus, string $outgoingPosition): array
    {
        $payload = [
            'status' => match ($outgoingStatus) {
                self::OUTGOING_RESIGNED, 'inactive' => 'inactive',
                self::OUTGOING_ON_LEAVE => 'on_leave',
                default => 'active',
            },
        ];

        if ($outgoingStatus === self::OUTGOING_WORKING) {
            $payload['position'] = $outgoingPosition;
            $payload['position_id'] = $this->positionIdByName($outgoingPosition);

            return $payload;
        }

        if (mb_strtolower(trim((string) $employee->position)) === mb_strtolower(self::POSITION_NAME)) {
            $payload['position'] = 'Nhân viên';
            $payload['position_id'] = $this->positionIdByName('Nhân viên');
        }

        return $payload;
    }

    private function closeOpenTenures(Employee $employee, Carbon $effectiveOn, ?string $decisionRef, ?string $note): void
    {
        EmployeePositionHistory::query()
            ->where('employee_id', $employee->id)
            ->where('status', EmployeePositionHistory::STATUS_HOLDING)
            ->whereNull('ended_at')
            ->get()
            ->each(function (EmployeePositionHistory $row) use ($effectiveOn, $decisionRef, $note) {
                $row->update([
                    'ended_at' => $this->tenureEndDate($row->started_at, $effectiveOn),
                    'status' => EmployeePositionHistory::STATUS_ENDED,
                    'end_reason' => $row->end_reason ?: EmployeePositionHistory::REASON_SUCCESSION,
                    'decision_ref' => $row->decision_ref ?: $decisionRef,
                    'note' => $note ?: $row->note,
                ]);
            });
    }

    private function openTenure(
        Employee $employee,
        User $holder,
        ?Position $position,
        bool $isDirectorRole,
        Carbon $startedAt,
        ?string $decisionRef,
        string $reason,
        ?User $actor,
        ?string $note = null
    ): EmployeePositionHistory {
        $positionName = $position?->name
            ?: ($isDirectorRole ? self::POSITION_NAME : ($employee->position ?: 'Nhân viên'));

        return EmployeePositionHistory::create([
            'employee_id' => $employee->id,
            'user_id' => $holder->id,
            'holder_name' => $holder->name,
            'holder_email' => $holder->email,
            'position_id' => $position?->id ?? $employee->position_id,
            'position_name' => $positionName,
            'department_id' => $employee->department_id,
            'department_name' => optional($employee->department)->name,
            'started_at' => $startedAt->toDateString(),
            'ended_at' => null,
            'end_reason' => $isDirectorRole ? null : $reason,
            'is_director_role' => $isDirectorRole,
            'status' => EmployeePositionHistory::STATUS_HOLDING,
            'decision_ref' => $decisionRef,
            'note' => $note,
            'created_by' => $actor?->id,
        ]);
    }

    private function tenureEndDate($startedAt, Carbon $effectiveOn): string
    {
        $start = Carbon::parse($startedAt)->startOfDay();
        $end = $effectiveOn->copy()->subDay();
        if ($end->lt($start)) {
            $end = $effectiveOn->copy();
        }

        return $end->toDateString();
    }

    private function directorPosition(): Position
    {
        return Position::query()->firstOrCreate(
            ['name' => self::POSITION_NAME],
            [
                'description' => 'Giám đốc điều hành công ty',
                'level' => 'C-level',
                'salary_range_min' => 0,
                'salary_range_max' => 0,
                'allowance' => 0,
                'base_salary' => 0,
            ]
        );
    }

    private function findPositionByName(string $name): ?Position
    {
        return Position::query()->where('name', $name)->first();
    }

    private function positionIdByName(string $name): ?int
    {
        return $this->findPositionByName($name)?->id;
    }

    private function boardDepartment(): ?Department
    {
        return Department::query()->where('code', self::BOARD_CODE)->first()
            ?: Department::query()->where('name', 'Ban Giám đốc')->first();
    }

    private function roleFlags(string $role): array
    {
        return [
            'is_admin' => false,
            'is_director' => false,
            'is_hr' => $role === 'hr',
            'is_accountant' => $role === 'accountant',
        ];
    }
}
