<?php

namespace App\Services;

use App\Models\OvertimeRequest;
use Carbon\Carbon;

class OvertimeActualCalculator
{
    public function shiftEnd(): string
    {
        return (string) config('overtime.shift_end', '17:30');
    }

    /**
     * @return array{actual_start: ?string, actual_end: ?string, actual_minutes: int}
     */
    public function compute(OvertimeRequest $overtime, ?Carbon $checkOut): array
    {
        $rawDate = $overtime->date;
        $date = $rawDate instanceof \DateTimeInterface
            ? Carbon::parse($rawDate)->toDateString()
            : trim((string) $rawDate);
        if ($date === '') {
            return $this->empty();
        }

        $approvedStart = $this->combine($date, $overtime->approvedStartTime() ?? $overtime->requestedStartTime());
        $approvedEnd = $this->combine($date, $overtime->approvedEndTime() ?? $overtime->requestedEndTime());
        if (! $approvedStart || ! $approvedEnd || $approvedEnd->lte($approvedStart)) {
            return $this->empty();
        }

        $shiftEnd = $this->combine($date, $this->shiftEnd()) ?? $approvedStart;
        $otStart = $approvedStart->greaterThan($shiftEnd) ? $approvedStart->copy() : $shiftEnd->copy();

        if (! $checkOut) {
            return $this->empty();
        }

        $checkOut = $checkOut->copy();
        if ($checkOut->lte($otStart)) {
            return $this->empty();
        }

        $otEnd = $checkOut->lessThan($approvedEnd) ? $checkOut->copy() : $approvedEnd->copy();
        if ($otEnd->lte($otStart)) {
            return $this->empty();
        }

        return [
            'actual_start' => $otStart->format('H:i:s'),
            'actual_end' => $otEnd->format('H:i:s'),
            'actual_minutes' => (int) $otStart->diffInMinutes($otEnd),
        ];
    }

    protected function combine(string $date, ?string $time): ?Carbon
    {
        $time = $this->normalizeTime($time);
        if ($time === null) {
            return null;
        }

        return Carbon::parse($date.' '.$time);
    }

    protected function normalizeTime(?string $time): ?string
    {
        $time = trim((string) $time);
        if ($time === '') {
            return null;
        }
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time.':00';
        }

        return $time;
    }

    /**
     * @return array{actual_start: null, actual_end: null, actual_minutes: int}
     */
    protected function empty(): array
    {
        return [
            'actual_start' => null,
            'actual_end' => null,
            'actual_minutes' => 0,
        ];
    }
}
