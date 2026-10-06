<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoredWebsite extends Model
{
    use HasFactory;

    protected $fillable = [
        'website_id',
        'user_id',
        'schedule',
        'alert_email',
        'alert_channel',
        'alert_threshold',
        'notify_on_critical_issues',
        'notify_on_ssl_expiry',
        'last_scanned_at',
        'next_scan_at',
        'is_active',
        'consecutive_failures',
        'status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'notify_on_critical_issues' => 'boolean',
        'notify_on_ssl_expiry' => 'boolean',
        'alert_threshold' => 'integer',
        'consecutive_failures' => 'integer',
        'last_scanned_at' => 'datetime',
        'next_scan_at' => 'datetime',
    ];

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(MonitoringAlert::class)->latest();
    }

    public function calculateNextScanAt(): \Carbon\Carbon
    {
        $base = now();
        return match ($this->schedule) {
            'weekly' => $base->addDays(7),
            default => $base->addDay(), // daily
        };
    }
}
