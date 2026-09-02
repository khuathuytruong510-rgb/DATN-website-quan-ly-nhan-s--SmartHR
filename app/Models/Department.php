<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
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

    public function scopeNotBoard(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('code')
                    ->orWhere('code', '!=', self::BOARD_CODE);
            })
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('name')
                    ->orWhere('name', '!=', self::BOARD_NAME);
            });
    }

    public function isBoard(): bool
    {
        return $this->code === self::BOARD_CODE || $this->name === self::BOARD_NAME;
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }
}
