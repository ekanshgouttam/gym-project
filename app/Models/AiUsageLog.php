<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One Claude API call made for a body scan report or comparison, with its token usage and estimated cost. */
class AiUsageLog extends Model
{
    public const UPDATED_AT = null;

    public const KINDS = ['scan_analysis' => 'Scan report', 'scan_comparison' => 'Before/after comparison'];

    protected $fillable = ['gym_id', 'user_id', 'kind', 'model', 'input_tokens', 'output_tokens', 'cache_write_tokens', 'cache_read_tokens', 'cost_usd', 'success', 'error'];

    protected $casts = ['success' => 'boolean', 'cost_usd' => 'float'];

    public function gym()
    {
        return $this->belongsTo(Gym::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Estimated USD cost from the per-million-token rates in config/services.php (anthropic.pricing).
     * Cache writes are billed at 1.25x the input rate; cache reads at the model's cache-read rate.
     */
    public static function estimateCost(?string $model, int $input, int $output, int $cacheWrite = 0, int $cacheRead = 0): float
    {
        $pricing = config('services.anthropic.pricing', []);
        $rates = $pricing[$model] ?? $pricing[config('services.anthropic.model')] ?? ['input' => 0, 'output' => 0, 'cache_read' => 0];

        return round((
            $input * $rates['input']
            + $output * $rates['output']
            + $cacheWrite * $rates['input'] * 1.25
            + $cacheRead * ($rates['cache_read'] ?? $rates['input'] * 0.1)
        ) / 1000000, 6);
    }

    public function totalTokens(): int
    {
        return $this->input_tokens + $this->output_tokens + $this->cache_write_tokens + $this->cache_read_tokens;
    }
}
