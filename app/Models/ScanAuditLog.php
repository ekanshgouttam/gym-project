<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/** Append-only: entries can be created but never changed or deleted. */
class ScanAuditLog extends Model
{
    public const UPDATED_AT = null;

    public const ACTIONS = [
        'baseline_created' => 'Baseline scan created at registration',
        'uploaded' => 'Scan uploaded',
        'analysis_retried' => 'AI analysis re-run',
        'voided' => 'Scan voided',
        'restored' => 'Scan restored',
        'corrected' => 'Scan details corrected',
        'override_granted' => 'Early upload allowed',
        'override_used' => 'Early upload used',
        'original_downloaded' => 'Original photo downloaded',
    ];

    protected $fillable = ['member_id', 'body_scan_id', 'actor_id', 'action', 'reason', 'changes', 'ip'];

    protected $casts = ['changes' => 'array', 'created_at' => 'datetime'];

    protected static function booted()
    {
        static::updating(function () {
            throw new LogicException('Audit log entries are immutable.');
        });
        static::deleting(function () {
            throw new LogicException('Audit log entries are immutable.');
        });
    }

    public static function record(User $member, string $action, ?User $actor = null, ?BodyScan $scan = null, ?string $reason = null, ?array $changes = null): self
    {
        return static::create([
            'member_id' => $member->id,
            'body_scan_id' => optional($scan)->id,
            'actor_id' => optional($actor)->id,
            'action' => $action,
            'reason' => $reason,
            'changes' => $changes,
            'ip' => request() ? request()->ip() : null,
        ]);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function scan()
    {
        return $this->belongsTo(BodyScan::class, 'body_scan_id');
    }

    public function actionLabel(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }
}
