<?php

namespace App\Support;

class LeaveTypes
{
    public static function all(): array
    {
        return config('leave.types', []);
    }

    public static function available(?\App\Models\Employee $employee = null): array
    {
        $types = self::all();
        if (! $employee?->isFemale()) {
            unset($types['maternity']);
        }

        return $types;
    }

    public static function keys(?\App\Models\Employee $employee = null): array
    {
        return array_keys($employee ? self::available($employee) : self::all());
    }

    public static function default(?\App\Models\Employee $employee = null): string
    {
        $keys = self::keys($employee);

        return $keys[0] ?? 'annual';
    }

    public static function label(?string $type): string
    {
        return self::all()[$type]['label'] ?? (string) $type;
    }

    public static function isPaid(?string $type): bool
    {
        return (bool) (self::all()[$type]['paid'] ?? false);
    }

    public static function validationRule(?\App\Models\Employee $employee = null): string
    {
        return 'in:'.implode(',', self::keys($employee));
    }
}
