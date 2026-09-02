<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    public const BOARD_CODE = 'BGD';
    public const BOARD_NAME = 'Ban Giám đốc';

    protected $fillable = [
        'name',
        'code',
        'manager',
        'description',
        'employee_count',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function isBoard(): bool
    {
        return strtoupper((string) $this->code) === self::BOARD_CODE
            || $this->name === self::BOARD_NAME;
    }

    public function scopeNotBoard($query)
    {
        return $query
            ->where(function ($q) {
                $q->whereNull('code')->orWhere('code', '!=', self::BOARD_CODE);
            })
            ->where(function ($q) {
                $q->whereNull('name')->orWhere('name', '!=', self::BOARD_NAME);
            });
    }
}
