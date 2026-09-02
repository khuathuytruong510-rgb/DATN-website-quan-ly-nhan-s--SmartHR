<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class ContractTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'contract_type',
        'content',
        'is_default',
        'status',
    ];

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function scopeActive($query)
    {
        if (Schema::hasColumn($this->getTable(), 'status')) {
            return $query->where('status', 'active');
        }

        if (Schema::hasColumn($this->getTable(), 'is_active')) {
            return $query->where('is_active', true);
        }

        return $query;
    }
}
