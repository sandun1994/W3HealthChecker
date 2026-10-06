<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanIssue extends Model
{
    use HasFactory;

    protected $fillable = [
        'scan_id',
        'rule_id',
        'category',
        'severity',
        'confidence',
        'title',
        'affected_resource',
        'evidence',
        'why_it_matters',
        'recommendation',
        'technical_details',
        'priority_order',
    ];

    protected $casts = [
        'evidence' => 'array',
        'priority_order' => 'integer',
    ];

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AuditRule::class, 'rule_id', 'id');
    }
}
