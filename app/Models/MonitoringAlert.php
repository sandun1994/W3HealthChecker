<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'monitored_website_id',
        'scan_id',
        'channel',
        'recipient',
        'subject',
        'severity',
        'message',
        'changes_summary',
        'status',
        'sent_at',
    ];

    protected $casts = [
        'changes_summary' => 'array',
        'sent_at' => 'datetime',
    ];

    public function monitoredWebsite(): BelongsTo
    {
        return $this->belongsTo(MonitoredWebsite::class);
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }
}
