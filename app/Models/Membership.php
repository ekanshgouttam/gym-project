<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Membership extends Model
{
    protected $fillable = ['user_id', 'gym_id', 'membership_plan_id', 'status', 'amount', 'starts_on', 'ends_on', 'activated_via', 'reviewed_by', 'reviewed_at', 'notes'];

    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'reviewed_at' => 'datetime', 'amount' => 'decimal:2'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gym()
    {
        return $this->belongsTo(Gym::class);
    }

    public function plan()
    {
        return $this->belongsTo(MembershipPlan::class, 'membership_plan_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->ends_on && $this->ends_on->copy()->endOfDay()->isFuture();
    }

    /** Activate from today, or from the day after a membership that is still running. */
    public function activate(string $via, ?User $reviewer = null): void
    {
        $current = static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->where('status', 'active')
            ->whereDate('ends_on', '>=', today())
            ->orderByDesc('ends_on')
            ->first();

        $start = $current ? $current->ends_on->copy()->addDay() : Carbon::today();

        $this->fill([
            'status' => 'active',
            'starts_on' => $start,
            'ends_on' => $start->copy()->addMonthsNoOverflow($this->plan->duration_months)->subDay(),
            'activated_via' => $via,
            'reviewed_by' => optional($reviewer)->id,
            'reviewed_at' => $reviewer ? now() : null,
        ])->save();
    }

    public function daysLeft(): int
    {
        if (! $this->ends_on) {
            return 0;
        }

        return max(0, Carbon::today()->diffInDays($this->ends_on, false) + 1);
    }

    public function progressPercent(): int
    {
        if (! $this->starts_on || ! $this->ends_on) {
            return 0;
        }
        $total = max(1, $this->starts_on->diffInDays($this->ends_on) + 1);
        $used = min($total, max(0, $this->starts_on->diffInDays(Carbon::today(), false)));

        return (int) round($used / $total * 100);
    }

    public function statusLabel(): string
    {
        if ($this->status === 'active' && ! $this->isActive()) {
            return 'Expired';
        }

        return $this->status === 'pending' ? 'Awaiting activation' : ucfirst($this->status);
    }
}
