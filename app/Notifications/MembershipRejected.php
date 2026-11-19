<?php

namespace App\Notifications;

use App\Models\BodyScan;
use App\Models\Membership;
use App\Models\User;

class MembershipRejected extends AppNotification
{
    private $membership;

    public function __construct(Membership $membership)
    {
        $this->membership = $membership;
    }

    protected function title(): string
    {
        return 'Membership request not approved';
    }

    protected function body(): string
    {
        return 'Your request for the ' . $this->membership->plan->name . ' plan was not approved' . ($this->membership->notes ? ': ' . $this->membership->notes : '.') . ' Please contact the front desk.';
    }

    protected function url(): ?string
    {
        return route('member.membership');
    }

    protected function kind(): string
    {
        return 'warning';
    }
}
