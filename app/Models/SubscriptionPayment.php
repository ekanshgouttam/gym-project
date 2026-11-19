<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Money a gym paid FlexFit for its subscription (INR). */
class SubscriptionPayment extends Model
{
    public const METHODS = ['upi' => 'UPI', 'bank' => 'Bank transfer', 'razorpay' => 'Razorpay', 'cash' => 'Cash', 'manual' => 'Other'];
    public const STATUSES = ['paid' => 'Paid', 'pending' => 'Pending', 'failed' => 'Failed', 'refunded' => 'Refunded'];

    protected $fillable = ['gym_id', 'gym_subscription_id', 'amount', 'method', 'reference', 'status', 'period_start', 'period_end', 'paid_at', 'notes', 'recorded_by'];

    protected $casts = ['period_start' => 'date', 'period_end' => 'date', 'paid_at' => 'datetime'];

    public function gym()
    {
        return $this->belongsTo(Gym::class);
    }

    public function subscription()
    {
        return $this->belongsTo(GymSubscription::class, 'gym_subscription_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
