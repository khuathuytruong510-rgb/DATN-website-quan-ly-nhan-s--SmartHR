<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VietnamHolidayCalendar
{
    /** @var array<int, int> */
    private static array $syncedYears = [];
    /**
     * Mùng 1 Tết Âm lịch (dương lịch) — dùng để xác định 5 ngày Tết Điều 112.
     *
     * @var array<int, string>
     */
    private array $lunarNewYear = [
        2025 => '2025-01-29',
        2026 => '2026-02-17',
        2027 => '2027-02-06',
        2028 => '2028-01-26',
        2029 => '2029-02-13',
        2030 => '2030-02-03',
    ];

    /**
     * Giỗ Tổ Hùng Vương 10/3 âm lịch.
     *
     * @var array<int, string>
     */
    private array $hungKings = [
        2025 => '2025-04-07',
        2026 => '2026-04-26',
        2027 => '2027-04-16',
        2028 => '2028-04-04',
        2029 => '2029-04-23',
        2030 => '2030-04-12',
    ];

    /**
     * @return list<array{date: string, name: string, type: string, is_paid: bool, work_rate: float, source: string, is_substitute: bool}>
     */
    public function buildYear(int $year): array
    {
        $rows = [];
        $this->push($rows, Carbon::create($year, 1, 1), 'Tết Dương lịch', false);

        $lunarNewYear = $this->lunarNewYear[$year] ?? null;
        if ($lunarNewYear) {
            $mungMot = Carbon::parse($lunarNewYear);
            $tetStart = $mungMot->copy()->subDay();
            $labels = [
                0 => 'Tết Âm lịch — 30/29 tháng Chạp',
                1 => 'Tết Âm lịch — mùng 1',
                2 => 'Tết Âm lịch — mùng 2',
                3 => 'Tết Âm lịch — mùng 3',
                4 => 'Tết Âm lịch — mùng 4',
            ];
            for ($i = 0; $i < 5; $i++) {
                $this->push($rows, $tetStart->copy()->addDays($i), $labels[$i], false);
            }
        }

        $this->push($rows, Carbon::create($year, 4, 30), 'Ngày Chiến thắng 30/4', false);
        $this->push($rows, Carbon::create($year, 5, 1), 'Ngày Quốc tế Lao động 1/5', false);

        $national = Carbon::create($year, 9, 2);
        $this->push($rows, $national, 'Quốc khánh 2/9', false);
        $this->push($rows, $this->nationalDayAdjacent($national), 'Quốc khánh — ngày liền kề', false);

        if (isset($this->hungKings[$year])) {
            $this->push($rows, Carbon::parse($this->hungKings[$year]), 'Giỗ Tổ Hùng Vương (10/3 âm lịch)', false);
        }

        if ($year >= (int) config('payroll.vietnam_culture_day_from_year', 2026)) {
            $this->push($rows, Carbon::create($year, 11, 24), 'Ngày Văn hóa Việt Nam 24/11', false);
        }

        $this->addSundaySubstitutes($rows);

        usort($rows, fn ($a, $b) => $a['date'] <=> $b['date']);

        return $rows;
    }

    public function syncYear(int $year): int
    {
        if (isset(self::$syncedYears[$year]) && DB::table('holidays')->whereYear('date', $year)->exists()) {
            return self::$syncedYears[$year];
        }

        if (! Schema::hasTable('holidays')) {
            return self::$syncedYears[$year] = 0;
        }

        $count = 0;
        foreach ($this->buildYear($year) as $row) {
            $exists = DB::table('holidays')->whereDate('date', $row['date'])->exists();
            if ($exists) {
                DB::table('holidays')->whereDate('date', $row['date'])->update([
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'is_paid' => $row['is_paid'],
                    'work_rate' => $row['work_rate'],
                    'source' => $row['source'],
                    'is_substitute' => $row['is_substitute'],
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('holidays')->insert($row + [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $count++;
        }

        return self::$syncedYears[$year] = $count;
    }

    /**
     * @return array<string, array{date: string, name: string, type: string, is_paid: bool, work_rate: float, source: string, is_substitute: bool}>
     */
    public function mapForMonth(int $month, int $year): array
    {
        $this->syncYear($year);
        $map = [];
        foreach ($this->buildYear($year) as $row) {
            if ((int) substr($row['date'], 5, 2) === $month) {
                $map[$row['date']] = $row;
            }
        }

        return $map;
    }

    /**
     * Ngày lễ hưởng lương trùng ngày làm (không phải Chủ nhật).
     *
     * @return list<string>
     */
    public function weekdayHolidayKeys(int $month, int $year): array
    {
        $keys = [];
        foreach ($this->mapForMonth($month, $year) as $date => $row) {
            if (! $row['is_paid']) {
                continue;
            }
            if (Carbon::parse($date)->isSunday()) {
                continue;
            }
            $keys[] = $date;
        }

        return $keys;
    }

    private function nationalDayAdjacent(Carbon $sept2): Carbon
    {
        $adjacent = ($sept2->isThursday() || $sept2->isFriday() || $sept2->isSaturday())
            ? $sept2->copy()->addDay()
            : $sept2->copy()->subDay();

        if ($adjacent->isSunday()) {
            $adjacent = $sept2->copy()->addDay();
            if ($adjacent->isSunday()) {
                $adjacent = $sept2->copy()->subDay();
            }
        }

        return $adjacent;
    }

    /**
     * @param  list<array{date: string, name: string, type: string, is_paid: bool, work_rate: float, source: string, is_substitute: bool}>  $rows
     */
    private function push(array &$rows, Carbon $day, string $name, bool $substitute): void
    {
        $key = $day->toDateString();
        foreach ($rows as $existing) {
            if ($existing['date'] === $key) {
                return;
            }
        }

        $rows[] = [
            'date' => $key,
            'name' => $name,
            'type' => 'national',
            'is_paid' => true,
            'work_rate' => (float) config('payroll.holiday_work_rate', 3.0),
            'source' => 'bldd_112',
            'is_substitute' => $substitute,
        ];
    }

    /**
     * Điều 112.3: lễ trùng ngày nghỉ hằng tuần → nghỉ bù ngày làm việc kế tiếp.
     *
     * @param  list<array{date: string, name: string, type: string, is_paid: bool, work_rate: float, source: string, is_substitute: bool}>  $rows
     */
    private function addSundaySubstitutes(array &$rows): void
    {
        $original = $rows;
        foreach ($original as $row) {
            $day = Carbon::parse($row['date']);
            if (! $day->isSunday()) {
                continue;
            }
            $next = $day->copy()->addDay();
            while ($next->isSunday()) {
                $next->addDay();
            }
            $this->push($rows, $next, $row['name'].' — nghỉ bù', true);
        }
    }
}
