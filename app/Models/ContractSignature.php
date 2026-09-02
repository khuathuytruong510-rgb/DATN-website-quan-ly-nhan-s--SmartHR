<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractSignature extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SIGNED = 'signed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_INVALID = 'invalid';

    public const ROLE_DIRECTOR = 'director';
    public const ROLE_EMPLOYEE = 'employee';

    protected $fillable = [
        'contract_id',
        'signer_id',
        'signer_role',
        'document_hash',
        'signature_value',
        'signed_document_path',
        'signed_at',
        'status',
        'provider',
        'provider_transaction_id',
        'verify_note',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signer_id');
    }

    public function isSigned(): bool
    {
        return $this->status === self::STATUS_SIGNED && filled($this->signature_value);
    }
}
