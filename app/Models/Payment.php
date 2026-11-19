<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A member's payment for a membership: online (Razorpay) or taken at the gym desk. */
class Payment extends Model
{
    public const METHODS = ['razorpay' => 'Online (Razorpay)', 'cash' => 'Cash', 'upi' => 'UPI', 'card' => 'Card', 'bank' => 'Bank transfer', 'desk' => 'At the desk'];
    public const DESK_METHODS = ['cash' => 'Cash', 'upi' => 'UPI', 'card' => 'Card', 'bank' => 'Bank transfer'];

    protected $fillable = ['user_id', 'membership_id', 'gateway', 'gateway_order_id', 'gateway_payment_id', 'amount_paise', 'currency', 'status', 'paid_at', 'recorded_by', 'note'];

    protected $casts = ['paid_at' => 'datetime'];

    public function membership()
    {
        return $this->belongsTo(Membership::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function amount(): float
    {
        return $this->amount_paise / 100;
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->gateway] ?? ucfirst($this->gateway);
    }
}
