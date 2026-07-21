<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractClause extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_type',
        'section_number',
        'section_title',
        'content',
        'order',
        'is_mandatory',
        'status',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
    ];

    public function scopeByType($query, $type)
    {
        return $query->where('contract_type', $type)->where('status', 'active');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc')->orderBy('section_number', 'asc');
    }
}
