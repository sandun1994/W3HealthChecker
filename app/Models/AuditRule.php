<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditRule extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'category',
        'title',
        'severity',
        'default_weight',
        'impact_description',
        'why_it_matters',
        'fix_guidance',
        'technical_fix_guidance',
        'is_active',
    ];

    protected $casts = [
        'default_weight' => 'float',
        'is_active' => 'boolean',
    ];

    public function issues(): HasMany
    {
        return $this->hasMany(ScanIssue::class, 'rule_id', 'id');
    }
}
