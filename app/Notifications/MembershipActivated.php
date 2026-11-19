<?php

namespace App\Notifications;

use App\Models\BodyScan;
use App\Models\Membership;
use App\Models\User;

class MembershipActivated extends AppNotification
{
    protected $category = null; // always delivered

    private $membership;

    public function __construct(Membership $membership)
    {
        $this->membership = $membership;
    }

    protected function title(): string
    {
        return 'Membership activated';
    }

    protected function body(): string
    {
        return "Your {$this->membership->plan->name} plan is active until {$this->membership->ends_on->format('d M Y')}.";
    }

    protected function url(): ?string
    {
        return route('member.membership');
    }

    protected function kind(): string
    {
        return 'success';
    }
}
