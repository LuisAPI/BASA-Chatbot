<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the files owned by this user.
     */
    public function files(): HasMany
    {
        return $this->hasMany(UserFile::class);
    }

    /**
     * Get the RAG chunks owned by this user.
     */
    public function ragChunks(): HasMany
    {
        return $this->hasMany(RagChunk::class);
    }

    /**
     * Get files shared with this user.
     */
    public function sharedFiles()
    {
        return UserFile::whereJsonContains('shared_with_users', $this->id)
            ->orWhere('is_public', true);
    }
}
