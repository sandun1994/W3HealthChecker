<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'key_prefix',
        'key_hash',
        'last_used_at',
        'requests_count',
        'is_active',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
        'requests_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a new API key pair returning [ApiKey $model, string $plainToken].
     */
    public static function generate(User $user, string $name): array
    {
        $rawSecret = Str::random(32);
        $plainToken = 'w3_live_' . $rawSecret;
        $keyPrefix = 'w3_live_' . substr($rawSecret, 0, 6);
        $keyHash = hash('sha256', $plainToken);

        $apiKey = static::create([
            'user_id' => $user->id,
            'name' => $name,
            'key_prefix' => $keyPrefix,
            'key_hash' => $keyHash,
            'is_active' => true,
        ]);

        return [$apiKey, $plainToken];
    }

    /**
     * Locate and validate an API key by raw token.
     */
    public static function findByToken(string $rawToken): ?self
    {
        $hash = hash('sha256', $rawToken);
        return static::where('key_hash', $hash)->where('is_active', true)->first();
    }
}
