<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\Notification;
use App\Models\User;

class RequestApprover
{
    public const HR = 'hr';
    public const DIRECTOR = 'director';

    public static function queueFor(?Employee $employee): string
    {
        $user = self::staffUser($employee);

        if ($user?->is_hr || $user?->is_director) {
            return self::DIRECTOR;
        }

        return self::HR;
    }

    public static function needsDirector(?Employee $employee): bool
    {
        return self::queueFor($employee) === self::DIRECTOR;
    }

    public static function isDirectorEmployee(?Employee $employee): bool
    {
        return (bool) self::staffUser($employee)?->is_director;
    }

    /** Hồ sơ chức vụ Giám đốc (kể cả chưa gắn user is_director). */
    public static function isDirectorProfile(?Employee $employee): bool
    {
        if (! $employee) {
            return false;
        }

        if (self::isDirectorEmployee($employee)) {
            return true;
        }

        $employee->loadMissing('positionDetail');
        $position = mb_strtolower(trim((string) ($employee->position ?: $employee->positionDetail?->name)));

        return $position === 'giám đốc';
    }

    /** HR không quản lý hồ sơ / yêu cầu của Giám đốc (vẫn quản lý Trợ lý, Thư ký Ban Giám đốc). */
    public static function hrMayManage(?User $actor, ?Employee $employee): bool
    {
        if (! $actor?->is_hr || ! $employee) {
            return false;
        }

        return ! self::isDirectorProfile($employee);
    }

    public static function canReview(?User $actor, ?Employee $employee): bool
    {
        if (! $actor || ! $employee) {
            return false;
        }

        if (self::isDirectorEmployee($employee)) {
            return false;
        }

        if (self::needsDirector($employee)) {
            return (bool) $actor->is_director;
        }

        return (bool) $actor->is_hr;
    }

    public static function queueLabel(?Employee $employee): string
    {
        return self::needsDirector($employee) ? 'Giám đốc' : 'HR';
    }

    public static function staffUser(?Employee $employee): ?User
    {
        if (! $employee) {
            return null;
        }

        $employee->loadMissing('user');

        if ($employee->user) {
            return $employee->user;
        }

        if ($employee->user_id) {
            return User::find($employee->user_id);
        }

        return $employee->email
            ? User::where('email', $employee->email)->first()
            : null;
    }

    public static function notifyQueue(Employee $employee, User $actor, string $title, string $message, array $data = []): Notification
    {
        $queue = self::queueFor($employee);

        return Notification::create([
            'sender_id' => $actor->id,
            'target' => $queue,
            'title' => $title,
            'message' => $message,
            'is_read' => false,
            'data' => array_merge($data, [
                'employee_id' => $employee->id,
                'queue' => $queue,
            ]),
        ]);
    }

    public static function submittedMessage(?Employee $employee): string
    {
        return 'Đơn hợp lệ theo hợp đồng. Đã gửi '.self::queueLabel($employee).' duyệt.';
    }
}
