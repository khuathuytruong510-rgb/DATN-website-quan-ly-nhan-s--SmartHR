<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractExpiryAction extends Model
{
    public const RENEW = 'renew';
    public const NOT_RENEW = 'not_renew';
    public const WAIT = 'wait';

    protected $fillable = [
        'contract_id',
        'employee_id',
        'decided_by',
        'decision',
        'reason',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function label(): string
    {
        return match ($this->decision) {
            self::RENEW => 'Gia hạn hợp đồng',
            self::NOT_RENEW => 'Không gia hạn',
            self::WAIT => 'Chờ quyết định',
            default => $this->decision,
        };
    }
}
