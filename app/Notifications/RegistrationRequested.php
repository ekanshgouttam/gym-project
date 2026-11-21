<?php

namespace App\Notifications;

use App\Models\User;

class RegistrationRequested extends AppNotification
{
    private $applicant;

    public function __construct(User $applicant)
    {
        $this->applicant = $applicant;
    }

    protected function title(): string
    {
        return 'New registration request';
    }

    protected function body(): string
    {
        return "{$this->applicant->name} ({$this->applicant->phone}) wants to join your gym.";
    }

    protected function url(): ?string
    {
        return route('gym-admin.registrations.index');
    }
}
