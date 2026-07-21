<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
