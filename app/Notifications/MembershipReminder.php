<?php

namespace App\Notifications;

use App\Models\Membership;

/** Fee reminders to members: membership expiring soon, expired, or requested but not paid. */
class MembershipReminder extends AppNotification
{
    protected $category = 'membership';

    private $membership;
    private $kind;
    private $days;

    public function __construct(Membership $membership, string $kind, int $days = 0)
    {
        $this->membership = $membership;
        $this->kind = $kind; // expiring, expired, payment_due
        $this->days = $days;
    }

    protected function title(): string
    {
        switch ($this->kind) {
            case 'expired':
                return 'Your membership has ended';
            case 'payment_due':
                return 'Payment pending';
            default:
                return $this->days === 1 ? 'Membership ends tomorrow' : "Membership ends in {$this->days} days";
        }
    }

    protected function body(): string
    {
        $plan = optional($this->membership->plan)->name ?: 'membership';
        switch ($this->kind) {
            case 'expired':
                return "Your {$plan} ended on {$this->membership->ends_on->format('d M')}. Renew to keep training and tracking your progress.";
            case 'payment_due':
                return "Your {$plan} request (₹" . number_format((float) $this->membership->amount) . ') is waiting for payment. Pay online or at the front desk to activate it.';
            default:
                return "Your {$plan} ends on {$this->membership->ends_on->format('d M Y')}. Renew now so there's no gap.";
        }
    }

    protected function url(): ?string
    {
        return route('member.membership');
    }

    protected function kind(): string
    {
        return $this->kind === 'expiring' ? 'info' : 'warning';
    }
}
