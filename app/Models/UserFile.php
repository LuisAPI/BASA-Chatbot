<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserFile extends Model
{
    protected $fillable = [
        'user_id',
        'original_name',
        'storage_path',
        'file_size',
        'file_type',
        'is_public',
        'shared_with_users', // JSON array of user IDs
        'processing_status', // pending, processing, completed, failed
        'error_message',
        'processed_at'
    ];

    protected $casts = [
        'shared_with_users' => 'array',
        'is_public' => 'boolean',
        'file_size' => 'integer',
        'processed_at' => 'datetime'
    ];

    /**
     * Get the user who owns this file.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the RAG chunks for this file.
     */
    public function ragChunks(): HasMany
    {
        return $this->hasMany(RagChunk::class, 'source', 'original_name')
            ->where('user_id', $this->user_id);
    }

    /**
     * Check if a user can access this file.
     */
    public function canBeAccessedBy(User $user): bool
    {
        // File owner can always access
        if ($this->user_id === $user->id) {
            return true;
        }

        // Public files can be accessed by anyone
        if ($this->is_public) {
            return true;
        }

        // Check if file is shared with the user
        if (is_array($this->shared_with_users) && in_array($user->id, $this->shared_with_users)) {
            return true;
        }

        return false;
    }

    /**
     * Share file with specific users.
     */
    public function shareWith(array $userIds): void
    {
        $currentShared = $this->shared_with_users ?? [];
        $this->update([
            'shared_with_users' => array_unique(array_merge($currentShared, $userIds))
        ]);
    }

    /**
     * Remove sharing with specific users.
     */
    public function unshareFrom(array $userIds): void
    {
        $currentShared = $this->shared_with_users ?? [];
        $this->update([
            'shared_with_users' => array_diff($currentShared, $userIds)
        ]);
    }
} 
