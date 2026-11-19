<?php

namespace App\Notifications;

use App\Models\BodyScan;
use App\Models\Membership;
use App\Models\User;

class WelcomeMember extends AppNotification
{
    private $memberCode;

    public function __construct(string $memberCode)
    {
        $this->memberCode = $memberCode;
    }

    protected function title(): string
    {
        return 'Welcome to ' . config('app.name') . '!';
    }

    protected function body(): string
    {
        return "Your Member ID is {$this->memberCode}. Show it at the front desk on your first visit.";
    }

    protected function url(): ?string
    {
        return route('member.profile');
    }
}
