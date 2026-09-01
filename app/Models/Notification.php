<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'target',
        'title',
        'message',
        'data',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function reads()
    {
        return $this->hasMany(NotificationRead::class);
    }

    public function isReadBy(?int $userId): bool
    {
        if (! $userId) {
            return (bool) $this->is_read;
        }

        if ($this->relationLoaded('reads')) {
            return $this->reads->contains(fn ($read) => (int) $read->user_id === (int) $userId);
        }

        return $this->reads()->where('user_id', $userId)->exists();
    }

    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}
