<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A FlexFit SaaS plan that a gym subscribes to (not a member's gym membership plan). */
class SubscriptionPlan extends Model
{
    protected $fillable = ['name', 'slug', 'price_monthly', 'price_yearly', 'member_limit', 'ai_scans_per_month', 'features', 'is_active', 'sort_order'];

    protected $casts = ['features' => 'array', 'is_active' => 'boolean'];

    public function subscriptions()
    {
        return $this->hasMany(GymSubscription::class);
    }

    public function priceFor(string $cycle): int
    {
        return $cycle === 'yearly' ? (int) ($this->price_yearly ?: $this->price_monthly * 12) : (int) $this->price_monthly;
    }
}
