<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Scan extends Model
{
    use HasFactory;

    protected $fillable = [
        'website_id',
        'user_id',
        'public_id',
        'target_url',
        'final_url',
        'status',
        'status_stage',
        'progress_percentage',
        'http_status_code',
        'response_time_ms',
        'overall_score',
        'status_label',
        'score_seo',
        'score_performance',
        'score_security',
        'score_accessibility',
        'score_mobile',
        'score_technical',
        'score_ai_readiness',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'progress_percentage' => 'integer',
        'http_status_code' => 'integer',
        'response_time_ms' => 'integer',
        'overall_score' => 'integer',
        'score_seo' => 'integer',
        'score_performance' => 'integer',
        'score_security' => 'integer',
        'score_accessibility' => 'integer',
        'score_mobile' => 'integer',
        'score_technical' => 'integer',
        'score_ai_readiness' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(ScanIssue::class)->orderBy('priority_order');
    }

    public function metric(): HasOne
    {
        return $this->hasOne(ScanMetric::class);
    }

    public function changes(): HasMany
    {
        return $this->hasMany(ScanChange::class, 'current_scan_id');
    }

    public function getFormattedStatusLabel(): string
    {
        if ($this->overall_score >= 90) return 'Excellent';
        if ($this->overall_score >= 80) return 'Good';
        if ($this->overall_score >= 70) return 'Needs Improvement';
        if ($this->overall_score >= 50) return 'Poor';
        return 'Critical';
    }
}
