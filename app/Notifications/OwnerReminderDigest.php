<?php

namespace App\Notifications;

/** Daily note to the gym owner: who was reminded today and who expired. */
class OwnerReminderDigest extends AppNotification
{
    private $counts;

    /** @param array{expiring:int, expired:int, payment_due:int} $counts */
    public function __construct(array $counts)
    {
        $this->counts = $counts;
    }

    protected function title(): string
    {
        return 'Fee reminders sent today';
    }

    protected function body(): string
    {
        return "{$this->counts['expiring']} expiring soon, {$this->counts['expired']} expired, {$this->counts['payment_due']} unpaid requests. See Plans & Fees for the list.";
    }

    protected function url(): ?string
    {
        return route('gym-admin.fees.index');
    }
}
