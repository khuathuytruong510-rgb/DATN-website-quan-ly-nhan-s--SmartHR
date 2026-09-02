<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceProfile extends Model
{
    use HasFactory;

    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    protected $fillable = [
        'employee_id',
        'face_embedding',
        'face_image',
        'status',
        'pending_face_embedding',
        'pending_face_image',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING && filled($this->pending_face_embedding);
    }

    public function isUsableForPunch(): bool
    {
        return filled($this->face_embedding);
    }

    public function previewImage(): ?string
    {
        return $this->pending_face_image ?: $this->face_image;
    }
}
