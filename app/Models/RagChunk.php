<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RagChunk extends Model
{
    protected $fillable = [
        'user_id',
        'source',
        'chunk',
        'embedding'
    ];

    protected $casts = [
        'embedding' => 'array'
    ];

    /**
     * Get the user who owns this chunk.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the file that this chunk belongs to.
     */
    public function userFile(): BelongsTo
    {
        return $this->belongsTo(UserFile::class, 'source', 'original_name')
            ->where('user_id', $this->user_id);
    }
} 