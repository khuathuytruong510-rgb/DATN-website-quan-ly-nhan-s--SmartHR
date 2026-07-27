<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\Payroll;
use Carbon\Carbon;

class EvaluationService
{
    /**
     * Lấy toàn bộ dữ liệu thực tế của nhân viên trong tháng.
     */
    public function getMonthlyStats(int $employeeId, string $month): array
    {
        [$year, $mon] = explode('-', $month);
        $start = Carbon::create((int)$year, (int)$mon, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        return [
            'attendance' => $this->getAttendanceStats($employeeId, $start, $end),
            'leave'      => $this->getLeaveStats($employeeId, $start, $end),
            'overtime'   => $this->getOvertimeStats($employeeId, $start, $end),
            'payroll'    => $this->getPayrollStats($employeeId, (int)$year, (int)$mon),
        ];
    }

    /**
     * Tự động đề xuất điểm từng tiêu chí dựa trên dữ liệu thực tế.
     */
    public function suggestScores(int $employeeId, string $month): array
    {
        $stats = $this->getMonthlyStats($employeeId, $month);
        $att   = $stats['attendance'];
        $leave = $stats['leave'];
        $ot    = $stats['overtime'];

        // Punctuality (0–10): đi đúng giờ
        $workDays    = max(1, $att['present_days'] + $att['late_days'] + $att['leave_early_days']);
        $lateRatio   = $att['late_days'] / $workDays;
        $punctuality = (int) round(10 * (1 - min(1, $lateRatio * 2)));
        $punctuality = max(0, min(10, $punctuality - min(4, $att['absent_days'])));

        // Task Completion (0–30): tỷ lệ có mặt
        $totalRec        = max(1, $att['total_records']);
        $presentRatio    = ($att['present_days'] + $att['leave_early_days'] + $att['late_days']) / $totalRec;
        $task_completion = max(0, min(30, (int) round(30 * min(1, $presentRatio))));

        // Quality (0–20): giờ làm + OT
        $avgH    = $att['avg_work_hours'];
        $qualityBase = $avgH >= 8 ? 18 : ($avgH >= 7 ? 15 : ($avgH >= 6 ? 12 : 8));
        $quality = min(20, $qualityBase + min(2, (int)($ot['approved_count'] / 2)));

        // Technical Skill (0–10): mặc định 7, HR tự chỉnh
        $technical_skill = 7;

        // Responsibility (0–10): trừ vắng + đột xuất
        $responsibility = max(0, 10 - min(5, $att['absent_days']) - min(3, $leave['urgent_count']));

        // Teamwork (0–10): mặc định 7
        $teamwork = 7;

        // Attitude (0–10): trừ theo muộn + về sớm
        $badDays  = $att['late_days'] + $att['leave_early_days'];
        $attitude = max(0, 10 - min(5, (int) round($badDays / $workDays * 10)));

        $score_total    = $punctuality + $task_completion + $quality + $technical_skill + $responsibility + $teamwork + $attitude;
        $classification = $this->classify($score_total);

        $rating = match(true) {
            $score_total >= 85 => 5,
            $score_total >= 70 => 4,
            $score_total >= 55 => 3,
            $score_total >= 40 => 2,
            default            => 1,
        };

        return [
            'punctuality'     => $punctuality,
            'task_completion' => $task_completion,
            'quality'         => $quality,
            'technical_skill' => $technical_skill,
            'responsibility'  => $responsibility,
            'teamwork'        => $teamwork,
            'attitude'        => $attitude,
            'score_total'     => $score_total,
            'classification'  => $classification,
            'rating'          => $rating,
            'summary'         => $this->buildSummary($stats, $score_total, $classification),
            'comments'        => $this->buildComments($att, $leave, $ot),
        ];
    }

    // ── Private helpers ────────────────────────────────────────

    private function getAttendanceStats(int $employeeId, Carbon $start, Carbon $end): array
    {
        $records = Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $details = $records->map(fn($r) => [
            'date'            => $r->date instanceof Carbon ? $r->date->format('d/m') : substr($r->date, 8, 2).'/'.substr($r->date, 5, 2),
            'status'          => $r->status,
            'status_label'    => $r->status_label,
            'check_in'        => $r->formatted_check_in,
            'check_out'       => $r->formatted_check_out,
            'work_hours'      => $r->work_hours,
            'late_minutes'    => $r->late_minutes,
            'early_leave_min' => $r->early_leave_minutes,
            'overtime_hours'  => $r->overtime_hours,
        ])->sortBy('date')->values()->toArray();

        return [
            'total_records'    => $records->count(),
            'present_days'     => $records->where('status', 'present')->count(),
            'late_days'        => $records->whereIn('status', ['late', 'late_and_leave_early'])->count(),
            'leave_early_days' => $records->whereIn('status', ['leave_early', 'late_and_leave_early'])->count(),
            'absent_days'      => $records->where('status', 'absent')->count(),
            'overtime_days'    => $records->where('overtime_hours', '>', 0)->count(),
            'total_late_min'   => (int) $records->sum('late_minutes'),
            'total_early_min'  => (int) $records->sum('early_leave_minutes'),
            'total_ot_hours'   => round((float) $records->sum('overtime_hours'), 1),
            'avg_work_hours'   => $records->count() > 0 ? round((float) $records->avg('work_hours'), 2) : 0,
            'details'          => $details,
        ];
    }

    private function getLeaveStats(int $employeeId, Carbon $start, Carbon $end): array
    {
        $leaves = LeaveRequest::where('employee_id', $employeeId)
            ->where(fn($q) => $q
                ->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                ->orWhereBetween('end_date',  [$start->toDateString(), $end->toDateString()])
            )->get();

        $approved = $leaves->where('status', 'approved');

        return [
            'total_count'    => $leaves->count(),
            'approved_count' => $approved->count(),
            'pending_count'  => $leaves->where('status', 'pending')->count(),
            'rejected_count' => $leaves->where('status', 'rejected')->count(),
            'urgent_count'   => $leaves->where('is_urgent', true)->count(),
            'approved_days'  => round((float) $approved->sum('days'), 1),
            'list'           => $leaves->map(fn($l) => [
                'start'  => $l->start_date instanceof Carbon ? $l->start_date->format('d/m') : $l->start_date,
                'end'    => $l->end_date instanceof Carbon   ? $l->end_date->format('d/m')   : $l->end_date,
                'days'   => $l->days,
                'type'   => $l->type ?? '',
                'status' => $l->status,
                'urgent' => (bool) $l->is_urgent,
            ])->values()->toArray(),
        ];
    }

    private function getOvertimeStats(int $employeeId, Carbon $start, Carbon $end): array
    {
        $records  = OvertimeRequest::where('employee_id', $employeeId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();

        return [
            'total_count'    => $records->count(),
            'approved_count' => $records->where('status', 'approved')->count(),
            'pending_count'  => $records->where('status', 'pending')->count(),
            'list'           => $records->map(fn($r) => [
                'date'   => $r->date instanceof Carbon ? $r->date->format('d/m') : $r->date,
                'start'  => $r->start_time,
                'end'    => $r->end_time,
                'status' => $r->status,
            ])->values()->toArray(),
        ];
    }

    private function getPayrollStats(int $employeeId, int $year, int $month): array
    {
        $p = Payroll::where('employee_id', $employeeId)
            ->where('year', $year)->where('month', $month)->first();

        if (! $p) return ['exists' => false];

        return [
            'exists'          => true,
            'base_salary'     => $p->base_salary    ?? 0,
            'total_salary'    => $p->total_salary    ?? 0,
            'allowance'       => $p->allowance       ?? 0,
            'bonus'           => $p->bonus           ?? 0,
            'deduction'       => $p->deduction       ?? 0,
            'insurance'       => $p->insurance       ?? 0,
            'tax'             => $p->tax             ?? 0,
            'overtime_salary' => $p->overtime_salary ?? 0,
        ];
    }

    private function classify(int $score): string
    {
        if ($score >= 85) return 'Xuất sắc';
        if ($score >= 70) return 'Tốt';
        if ($score >= 50) return 'Trung bình';
        return 'Yếu';
    }

    private function buildSummary(array $stats, int $score, string $cls): string
    {
        $att = $stats['attendance'];
        $lv  = $stats['leave'];
        $ot  = $stats['overtime'];
        $parts = ["Nhân viên đạt {$score}/100 điểm — phân loại: {$cls}."];
        if ($att['total_records'] > 0) {
            $parts[] = "Chấm công: {$att['present_days']} ngày đúng giờ, {$att['late_days']} ngày đi muộn, {$att['absent_days']} ngày vắng mặt.";
        }
        if ($lv['approved_days'] > 0)    $parts[] = "Nghỉ phép được duyệt: {$lv['approved_days']} ngày.";
        if ($ot['approved_count'] > 0)   $parts[] = "Tăng ca được duyệt: {$ot['approved_count']} lần.";
        return implode(' ', $parts);
    }

    private function buildComments(array $att, array $leave, array $ot): string
    {
        $parts = [];
        if ($att['late_days'] > 3) {
            $parts[] = "Cần cải thiện đi đúng giờ (đi muộn {$att['late_days']} lần, tổng {$att['total_late_min']} phút).";
        } elseif ($att['late_days'] === 0) {
            $parts[] = "Chấp hành tốt giờ giấc, không có ngày đi muộn.";
        }
        if ($att['absent_days'] > 0)       $parts[] = "Có {$att['absent_days']} ngày vắng mặt không phép.";
        if ($ot['approved_count'] > 0)     $parts[] = "Tích cực tăng ca ({$ot['approved_count']} lần được duyệt).";
        if ($leave['urgent_count'] > 0)    $parts[] = "Có {$leave['urgent_count']} đơn nghỉ đột xuất.";
        return $parts ? implode(' ', $parts) : 'Nhân viên hoàn thành công việc bình thường trong tháng.';
    }
}
