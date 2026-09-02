<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\SupportRequest;
use App\Models\User;
use App\Support\HrApprovalNotifier;
use App\Support\RequestApprover;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SupportRequestService
{
    public function submit(Employee $employee, User $actor, array $data, ?UploadedFile $attachment = null): SupportRequest
    {
        $path = $attachment?->store('support_attachments');

        return DB::transaction(function () use ($employee, $actor, $data, $path) {
            $ticket = SupportRequest::create([
                'employee_id' => $employee->id,
                'subject' => $data['subject'],
                'message' => $data['message'],
                'type' => $data['type'],
                'attachment' => $path,
                'status' => SupportRequest::PENDING,
            ]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'support_submitted',
                'meta' => $data['subject'],
            ]);

            RequestApprover::notifyQueue(
                $employee,
                $actor,
                'Yêu cầu hỗ trợ cần duyệt',
                sprintf(
                    '%s gửi yêu cầu hỗ trợ: %s. Vui lòng duyệt rồi xử lý.',
                    $employee->name,
                    $ticket->subject
                ),
                [
                    'type' => 'support_request',
                    'support_request_id' => $ticket->id,
                ]
            );

            return $ticket;
        });
    }

    public function approve(SupportRequest $ticket, User $actor): SupportRequest
    {
        $ticket->loadMissing('employee');
        $this->assertCanReview($actor, $ticket->employee);

        return DB::transaction(function () use ($ticket, $actor) {
            $row = SupportRequest::query()->whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $row->loadMissing('employee');
            $this->assertCanReview($actor, $row->employee);
            if ($row->status !== SupportRequest::PENDING) {
                throw new RuntimeException('Chỉ duyệt yêu cầu đang chờ duyệt.');
            }

            $row->update(['status' => SupportRequest::PROCESSING]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'support_approved',
                'meta' => $row->subject,
            ]);

            HrApprovalNotifier::approved($row->employee_id, $actor, 'Yêu cầu hỗ trợ', [
                'type' => 'support_request',
                'support_request_id' => $row->id,
            ]);

            return $row->fresh();
        });
    }

    public function resolve(SupportRequest $ticket, User $actor, ?string $reply = null): SupportRequest
    {
        $ticket->loadMissing('employee');
        $this->assertCanReview($actor, $ticket->employee);

        $reply = $reply !== null ? trim($reply) : '';

        return DB::transaction(function () use ($ticket, $actor, $reply) {
            $row = SupportRequest::query()->whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $row->loadMissing('employee');
            $this->assertCanReview($actor, $row->employee);
            if ($row->status !== SupportRequest::PROCESSING) {
                throw new RuntimeException('Chỉ đánh dấu đã xử lý sau khi đã duyệt yêu cầu.');
            }

            $row->update([
                'status' => SupportRequest::RESOLVED,
                'hr_reply' => $reply !== '' ? $reply : $row->hr_reply,
            ]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'support_resolved',
                'meta' => $row->subject,
            ]);

            $handler = RequestApprover::queueLabel($row->employee);
            $message = 'Yêu cầu hỗ trợ đã được xử lý.';
            if ($reply !== '') {
                $message .= ' Kết quả: '.$reply;
            }
            $message .= ' Bạn có thể gửi phản hồi về kết quả này cho '.$handler.'.';

            HrApprovalNotifier::send(
                $row->employee_id,
                $actor,
                'Yêu cầu hỗ trợ đã được xử lý',
                $message,
                [
                    'type' => 'support_resolved',
                    'support_request_id' => $row->id,
                ]
            );

            return $row->fresh();
        });
    }

    public function submitFeedback(SupportRequest $ticket, Employee $employee, User $actor, string $feedback): SupportRequest
    {
        $feedback = trim($feedback);
        if ($feedback === '') {
            throw new RuntimeException('Nhập nội dung phản hồi về kết quả đã xử lý.');
        }

        return DB::transaction(function () use ($ticket, $employee, $actor, $feedback) {
            $row = SupportRequest::query()->whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            if ((int) $row->employee_id !== (int) $employee->id) {
                throw new RuntimeException('Bạn không gửi được phản hồi cho yêu cầu này.');
            }
            if ($row->status !== SupportRequest::RESOLVED) {
                throw new RuntimeException('Chỉ phản hồi sau khi yêu cầu đã được xử lý xong.');
            }
            if (filled($row->employee_feedback)) {
                throw new RuntimeException('Bạn đã gửi phản hồi cho yêu cầu này.');
            }

            $row->update(['employee_feedback' => $feedback]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'support_feedback',
                'meta' => $row->subject,
            ]);

            RequestApprover::notifyQueue(
                $employee,
                $actor,
                'Phản hồi kết quả hỗ trợ',
                sprintf('%s phản hồi yêu cầu “%s”: %s', $employee->name, $row->subject, $feedback),
                [
                    'type' => 'support_feedback',
                    'support_request_id' => $row->id,
                ]
            );

            return $row->fresh();
        });
    }

    private function assertCanReview(User $actor, ?Employee $employee): void
    {
        if (! RequestApprover::canReview($actor, $employee)) {
            throw new RuntimeException(
                RequestApprover::isDirectorEmployee($employee)
                    ? 'HR không quản lý yêu cầu của Giám đốc.'
                    : (RequestApprover::needsDirector($employee)
                        ? 'Yêu cầu hỗ trợ của HR do Giám đốc duyệt và xử lý.'
                        : 'Chỉ HR được duyệt yêu cầu hỗ trợ của nhân viên.')
            );
        }
    }
}
