<?php

namespace App\Notifications;

use App\Models\BodyScan;
use App\Models\Membership;
use App\Models\User;

class MembershipAwaitingApproval extends AppNotification
{
    private $member;
    private $membership;

    public function __construct(User $member, Membership $membership)
    {
        $this->member = $member;
        $this->membership = $membership;
    }

    protected function title(): string
    {
        return 'New membership awaiting approval';
    }

    protected function body(): string
    {
        return "{$this->member->name} requested the {$this->membership->plan->name} plan.";
    }

    protected function url(): ?string
    {
        return route('manage.memberships.index');
    }
}
