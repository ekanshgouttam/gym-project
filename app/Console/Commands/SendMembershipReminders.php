<?php

namespace App\Console\Commands;

use App\Models\Gym;
use App\Models\Membership;
use App\Models\MembershipReminder as SentReminder;
use App\Models\User;
use App\Notifications\MembershipReminder;
use App\Notifications\OwnerReminderDigest;
use App\Support\Roles;
use Illuminate\Console\Command;

/**
 * Daily fee housekeeping, per gym and its Settings:
 *  - members whose membership ends in N days (default 7, 3 and 1) get an expiry reminder;
 *  - memberships past their end date are marked expired and the member is told;
 *  - membership requests unpaid for 2+ days get one payment reminder;
 *  - the owner gets a one-line summary.
 * Every reminder is logged, so running it twice a day never double-sends.
 */
class SendMembershipReminders extends Command
{
    protected $signature = 'memberships:remind';

    protected $description = 'Send membership expiry and payment reminders, and mark ended memberships expired';

    public function handle(): int
    {
        $totals = ['expiring' => 0, 'expired' => 0, 'payment_due' => 0];

        Gym::where('is_active', true)->get()->each(function (Gym $gym) use (&$totals) {
            $counts = ['expiring' => 0, 'expired' => 0, 'payment_due' => 0];

            // 1. Ended memberships → expired (always, so dashboards stay accurate).
            Membership::with('user', 'plan')->where('gym_id', $gym->id)->where('status', 'active')->whereDate('ends_on', '<', today())->get()
                ->each(function (Membership $m) use ($gym, &$counts) {
                    $m->update(['status' => 'expired']);
                    $hasNext = Membership::where('user_id', $m->user_id)->where('status', 'active')->whereDate('ends_on', '>=', today())->exists();
                    if (! $hasNext && $gym->setting('expiry_reminders') && $this->once($m, 'expired')) {
                        $m->user->notify(new MembershipReminder($m, 'expired'));
                        $counts['expired']++;
                    }
                });

            // 2. Expiring soon.
            if ($gym->setting('expiry_reminders')) {
                foreach ((array) $gym->setting('reminder_days') as $days) {
                    $days = (int) $days;
                    Membership::with('user', 'plan')->where('gym_id', $gym->id)->where('status', 'active')
                        ->whereDate('ends_on', today()->addDays($days))->get()
                        ->each(function (Membership $m) use ($days, &$counts) {
                            // Skip members who already renewed (a later membership exists).
                            $renewed = Membership::where('user_id', $m->user_id)->where('id', '!=', $m->id)->where('status', 'active')->whereDate('starts_on', '>', $m->ends_on)->exists();
                            if (! $renewed && $this->once($m, "expiring_{$days}")) {
                                $m->user->notify(new MembershipReminder($m, 'expiring', $days));
                                $counts['expiring']++;
                            }
                        });
                }
            }

            // 3. Requested but unpaid for 2+ days.
            if ($gym->setting('payment_reminders')) {
                Membership::with('user', 'plan')->where('gym_id', $gym->id)->where('status', 'pending')->where('created_at', '<=', now()->subDays(2))->get()
                    ->each(function (Membership $m) use (&$counts) {
                        if ($this->once($m, 'payment_due')) {
                            $m->user->notify(new MembershipReminder($m, 'payment_due'));
                            $counts['payment_due']++;
                        }
                    });
            }

            if (array_sum($counts) > 0 && $gym->setting('notify_owner')) {
                User::role(Roles::GYM_ADMIN)->where('gym_id', $gym->id)->where('status', 'active')->get()
                    ->each(function ($owner) use ($counts) { $owner->notify(new OwnerReminderDigest($counts)); });
            }

            foreach ($counts as $k => $v) {
                $totals[$k] += $v;
            }
        });

        $this->info("Reminders sent — expiring: {$totals['expiring']}, expired: {$totals['expired']}, payment due: {$totals['payment_due']}.");

        return 0;
    }

    /** True the first time a reminder kind is recorded for a membership. */
    private function once(Membership $m, string $kind): bool
    {
        return SentReminder::firstOrCreate(['membership_id' => $m->id, 'kind' => $kind])->wasRecentlyCreated;
    }
}
