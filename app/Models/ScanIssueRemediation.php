<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanIssueRemediation extends Model
{
    use HasFactory;

    protected $fillable = [
        'scan_issue_id',
        'user_id',
        'status',
        'dismissed_reason',
        'verified_scan_id',
        'user_notes',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(ScanIssue::class, 'scan_issue_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifiedScan(): BelongsTo
    {
        return $this->belongsTo(Scan::class, 'verified_scan_id');
    }
}
