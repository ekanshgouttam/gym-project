<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A gym's FlexFit subscription. A gym keeps one current row; history lives in subscription_payments. */
class GymSubscription extends Model
{
    public const STATUSES = ['trial' => 'Trial', 'active' => 'Active', 'past_due' => 'Past due', 'cancelled' => 'Cancelled', 'expired' => 'Expired'];

    protected $fillable = ['gym_id', 'subscription_plan_id', 'status', 'billing_cycle', 'amount', 'starts_on', 'ends_on', 'cancelled_at'];

    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'cancelled_at' => 'datetime'];

    public function gym()
    {
        return $this->belongsTo(Gym::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    /** New gyms start on the default plan's free trial (Super Admin → Settings). */
    public static function startTrial(Gym $gym): self
    {
        $plan = SubscriptionPlan::where('slug', PlatformSetting::get('default_plan'))->first()
            ?: SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->firstOrFail();

        return static::create([
            'gym_id' => $gym->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'trial',
            'billing_cycle' => 'monthly',
            'amount' => $plan->price_monthly,
            'starts_on' => today(),
            'ends_on' => today()->addDays(max(0, (int) PlatformSetting::get('trial_days'))),
        ]);
    }

    /** Trial or paid subscriptions. */
    public function scopeLive($query)
    {
        return $query->whereIn('status', ['trial', 'active']);
    }

    /** Monthly recurring revenue in INR (yearly spread over 12 months; trials and unpaid count 0). */
    public function mrr(): float
    {
        if ($this->status !== 'active') {
            return 0;
        }

        return $this->billing_cycle === 'yearly' ? $this->amount / 12 : (float) $this->amount;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function badgeClass(): string
    {
        return [
            'trial' => 'bg-sky-500/15 text-sky-300',
            'active' => 'bg-emerald-500/15 text-emerald-300',
            'past_due' => 'bg-ember-500/15 text-ember-300',
            'cancelled' => 'bg-slate-500/15 text-slate-400',
            'expired' => 'bg-rose-500/15 text-rose-300',
        ][$this->status] ?? 'bg-slate-500/15 text-slate-400';
    }
}
