<?php

namespace Modules\NabdBridge\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $token
 * @property string|null $plain_token
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class NabdApiToken extends Model
{
    protected $table = 'nabd_api_tokens';

    protected $fillable = [
        'name',
        'token',
        'plain_token',
        'last_used_at',
        'expires_at',
    ];

    protected $hidden = [
        'token',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Check whether this token is expired.
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Generate a new random plain-text token and store its hash.
     * Returns the plain-text token (shown only once).
     */
    public static function generateToken(): array
    {
        $plain = bin2hex(random_bytes(32));
        $hashed = hash('sha256', $plain);

        return ['plain' => $plain, 'hashed' => $hashed];
    }
}
