<?php

namespace App\Support;

use App\Models\Notification;
use App\Models\User;

class HrApprovalNotifier
{
    public static function approved(int $employeeId, ?User $actor, string $subject, array $data = []): Notification
    {
        $title = $subject.' đã được duyệt';

        return self::send($employeeId, $actor, $title, $title.'.', $data);
    }

    public static function rejected(int $employeeId, ?User $actor, string $subject, ?string $reason = null, array $data = []): Notification
    {
        $title = $subject.' bị từ chối';
        $message = $reason ? $title.'. Lý do: '.$reason : $title.'.';

        return self::send($employeeId, $actor, $title, $message, $data);
    }

    public static function send(int $employeeId, ?User $actor, string $title, string $message, array $data = []): Notification
    {
        return Notification::create([
            'sender_id' => $actor?->id,
            'target' => 'employee',
            'title' => $title,
            'message' => $message,
            'is_read' => false,
            'data' => array_merge($data, ['employee_id' => $employeeId]),
        ]);
    }
}
