<?php

namespace App\Notifications;

class PlanAssigned extends AppNotification
{
    protected $category = 'plans';

    private $kind;
    private $title;

    public function __construct(string $kind, string $title)
    {
        $this->kind = $kind;
        $this->title = $title;
    }

    protected function title(): string
    {
        return $this->kind === 'diet' ? 'New diet plan' : 'New workout plan';
    }

    protected function body(): string
    {
        return "Your trainer assigned “{$this->title}”. Open it to see what's planned.";
    }

    protected function url(): ?string
    {
        return route($this->kind === 'diet' ? 'member.diet' : 'member.workouts');
    }
}
