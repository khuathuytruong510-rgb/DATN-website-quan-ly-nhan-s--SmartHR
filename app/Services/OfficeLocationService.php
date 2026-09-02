<?php

namespace App\Services;

use App\Exceptions\AttendanceException;

class OfficeLocationService
{
    public function latitude(): float
    {
        return (float) config('attendance.office_latitude');
    }

    public function longitude(): float
    {
        return (float) config('attendance.office_longitude');
    }

    public function allowedDistance(): float
    {
        return (float) config('attendance.allowed_distance_meters', 60);
    }

    public function settings(): array
    {
        return [
            'office_latitude' => $this->latitude(),
            'office_longitude' => $this->longitude(),
            'allowed_distance' => $this->allowedDistance(),
        ];
    }

    public function distance(float $latitude, float $longitude): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($this->latitude() - $latitude);
        $dLon = deg2rad($this->longitude() - $longitude);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($latitude)) * cos(deg2rad($this->latitude())) * sin($dLon / 2) ** 2;

        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    /**
     * @return array{distance: float, location: string}
     */
    public function assertWithinRange(?float $latitude, ?float $longitude): array
    {
        if ($latitude === null || $longitude === null) {
            throw new AttendanceException('Không xác định được vị trí GPS. Hãy bật vị trí trên trình duyệt rồi thử lại.');
        }

        $distance = $this->distance($latitude, $longitude);
        $allowed = $this->allowedDistance();

        if ($distance > $allowed) {
            throw new AttendanceException(
                'Bạn đang cách văn phòng '.round($distance).' mét. Chỉ được chấm công trong phạm vi '.round($allowed).'m.',
                400,
                [
                    'distance' => round($distance, 2),
                    'allowed_distance' => $allowed,
                ]
            );
        }

        return [
            'distance' => round($distance, 2),
            'location' => sprintf('Vị trí: %.6f, %.6f', $latitude, $longitude),
        ];
    }
}
