<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Website extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'client_id',
        'domain',
        'scheme',
        'canonical_url',
        'is_monitored',
        'monitoring_frequency',
        'last_scanned_at',
    ];

    protected $casts = [
        'is_monitored' => 'boolean',
        'last_scanned_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class)->latest();
    }

    public function latestScan(): HasOne
    {
        return $this->hasOne(Scan::class)->latestOfMany();
    }

    public function monitoredWebsite(): HasOne
    {
        return $this->hasOne(MonitoredWebsite::class);
    }

    public function changes(): HasMany
    {
        return $this->hasMany(ScanChange::class)->latest();
    }
}
