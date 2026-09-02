<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractExpiryAlert extends Model
{
    public const MILESTONE_30 = '30';
    public const MILESTONE_7 = '7';
    public const MILESTONE_EXPIRED = 'expired';
    public const MILESTONE_OVERDUE = 'overdue';

    protected $fillable = [
        'contract_id',
        'milestone',
        'target',
        'notification_id',
        'days_remaining',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }
}
