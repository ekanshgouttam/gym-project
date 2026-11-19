<?php

namespace App\Support;

use App\Models\User;
use App\Services\BodyScanService;

/** Derived numbers shown across the member dashboard. */
class MemberStats
{
    /** @var User */
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function currentWeight(): ?float
    {
        $log = $this->user->weightLogs()->orderByDesc('logged_on')->first();

        return $log ? $log->weight_kg : optional($this->user->memberProfile)->starting_weight_kg;
    }

    public function weightChange(): ?float
    {
        $start = optional($this->user->memberProfile)->starting_weight_kg
            ?? optional($this->user->weightLogs()->orderBy('logged_on')->first())->weight_kg;
        $now = $this->currentWeight();

        return ($start && $now) ? round($now - $start, 1) : null;
    }

    public function bmi(): ?float
    {
        $h = optional($this->user->memberProfile)->height_cm;
        $w = $this->currentWeight();

        return ($h && $w) ? round($w / pow($h / 100, 2), 1) : null;
    }

    public static function bmiLabel(?float $bmi): string
    {
        if ($bmi === null) {
            return '—';
        }
        if ($bmi < 18.5) {
            return 'Underweight';
        }
        if ($bmi < 25) {
            return 'Healthy range';
        }

        return $bmi < 30 ? 'Overweight' : 'Obese range';
    }

    public function visitsThisMonth(): int
    {
        return $this->user->attendances()->where('checked_in_at', '>=', now()->startOfMonth())->count();
    }

    /** Consecutive weeks (ending this week) with at least one visit. */
    public function weekStreak(): int
    {
        $weeks = $this->user->attendances()
            ->where('checked_in_at', '>=', now()->subWeeks(52))
            ->pluck('checked_in_at')
            ->map(function ($d) { return $d->copy()->startOfWeek()->toDateString(); })
            ->unique()
            ->flip();

        $streak = 0;
        $cursor = now()->startOfWeek();
        // This week may not have a visit yet; don't break the streak for it.
        if (! isset($weeks[$cursor->toDateString()])) {
            $cursor->subWeek();
        }
        while (isset($weeks[$cursor->toDateString()])) {
            $streak++;
            $cursor->subWeek();
        }

        return $streak;
    }

    public function scanEligibility(): array
    {
        return app(BodyScanService::class)->eligibility($this->user);
    }

    /** Today's day from the active workout plan (matched by weekday). */
    public function todaysWorkout(): ?array
    {
        $plan = $this->user->workoutPlans()->where('is_active', true)->latest()->first();
        if (! $plan || empty($plan->days)) {
            return null;
        }

        $weekday = now()->format('l');
        foreach ($plan->days as $day) {
            if (strcasecmp($day['day'] ?? '', $weekday) === 0) {
                return $day + ['plan' => $plan->title];
            }
        }

        return ['day' => $weekday, 'focus' => 'Rest & recovery', 'exercises' => [], 'plan' => $plan->title];
    }
}
