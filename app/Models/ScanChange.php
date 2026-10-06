<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanChange extends Model
{
    use HasFactory;

    protected $fillable = [
        'website_id',
        'current_scan_id',
        'previous_scan_id',
        'change_type',
        'category',
        'severity',
        'title',
        'description',
        'old_value',
        'new_value',
    ];

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function currentScan(): BelongsTo
    {
        return $this->belongsTo(Scan::class, 'current_scan_id');
    }

    public function previousScan(): BelongsTo
    {
        return $this->belongsTo(Scan::class, 'previous_scan_id');
    }
}
