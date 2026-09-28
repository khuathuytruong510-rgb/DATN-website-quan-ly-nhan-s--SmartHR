<?php

namespace App\Support;

class LeaveTypes
{
    public const SPOUSE_BIRTH = 'spouse_birth';

    public static function all(): array
    {
        return config('leave.types', []);
    }

    public static function available(?\App\Models\Employee $employee = null): array
    {
        $types = self::all();
        // Nghỉ thai sản khi sinh con: chỉ lao động nữ (Bộ luật Lao động Điều 139).
        if (! $employee?->isFemale()) {
            unset($types['maternity']);
        }
        // Nghỉ thai sản khi vợ sinh con: chỉ lao động nam (Luật BHXH 2024 Điều 53, Luật Dân số 2025).
        if ($employee && ! $employee->isMale()) {
            unset($types[self::SPOUSE_BIRTH]);
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

        if ($employee?->isFemale() && in_array('maternity', $keys, true)) {
            return 'maternity';
        }
        if ($employee && in_array('annual', $keys, true)) {
            return 'annual';
        }

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
