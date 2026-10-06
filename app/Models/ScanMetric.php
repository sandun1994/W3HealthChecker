<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'scan_id',
        'headers',
        'ssl_data',
        'dns_records',
        'open_graph',
        'twitter_card',
        'structured_data',
        'meta_summary',
        'headings_summary',
        'links_summary',
        'assets_summary',
    ];

    protected $casts = [
        'headers' => 'array',
        'ssl_data' => 'array',
        'dns_records' => 'array',
        'open_graph' => 'array',
        'twitter_card' => 'array',
        'structured_data' => 'array',
        'meta_summary' => 'array',
        'headings_summary' => 'array',
        'links_summary' => 'array',
        'assets_summary' => 'array',
    ];

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }
}
