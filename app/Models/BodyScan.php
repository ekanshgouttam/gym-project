<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Every row is an accepted scan: photos passed validation and are kept permanently.
 * `status` tracks only the AI analysis (pending → completed | failed), which can be re-run.
 * Voided scans (admin correction) stay in history but don't count toward the 30-day rule.
 */
class BodyScan extends Model
{
    /** Minimum days between accepted scans. */
    public const INTERVAL_DAYS = 30;

    public const VIEWS = ['front', 'side', 'back'];

    public const MAX_ANALYSIS_ATTEMPTS = 3;

    public const LEANNESS = [
        'very_lean' => 'Very lean appearance',
        'lean' => 'Lean appearance',
        'moderate' => 'Moderate',
        'soft' => 'Softer appearance',
        'unclear' => 'Not clear from photos',
    ];

    public const DEFINITION = [
        'low' => 'Low visible definition',
        'moderate' => 'Moderate visible definition',
        'high' => 'High visible definition',
        'unclear' => 'Not clear from photos',
    ];

    protected $fillable = [
        'user_id', 'is_baseline', 'front_path', 'side_path', 'back_path', 'originals', 'quality',
        'weight_kg', 'height_cm', 'status', 'analysis_attempts', 'physique_score', 'summary', 'analysis', 'error', 'analyzed_at',
    ];

    protected $casts = [
        'is_baseline' => 'boolean',
        'originals' => 'array',
        'quality' => 'array',
        'analysis' => 'array',
        'analyzed_at' => 'datetime',
        'voided_at' => 'datetime',
        'weight_kg' => 'float',
        'height_cm' => 'float',
        'body_fat_estimate' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function voidedBy()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function auditLogs()
    {
        return $this->hasMany(ScanAuditLog::class);
    }

    public function scopeAccepted($query)
    {
        return $query->whereNull('voided_at');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function photoPath(string $view): ?string
    {
        return in_array($view, self::VIEWS, true) ? $this->{$view . '_path'} : null;
    }

    public function photoHashes(): array
    {
        return collect($this->originals ?? [])->pluck('sha256')->filter()->values()->all();
    }

    public function canRetryAnalysis(): bool
    {
        return $this->status === 'failed' && ! $this->isVoided() && $this->analysis_attempts < self::MAX_ANALYSIS_ATTEMPTS;
    }

    public function label(): string
    {
        return ($this->is_baseline ? 'Baseline · ' : '') . $this->created_at->format('d M Y');
    }
}
