<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Base for in-app (database) notifications. Each subclass sets a title, body, link and
 * the member preference category it belongs to; null category = always delivered.
 */
abstract class AppNotification extends Notification
{
    use Queueable;

    /** @var string|null one of MemberProfile::DEFAULT_PREFS keys */
    protected $category = null;

    abstract protected function title(): string;

    abstract protected function body(): string;

    protected function url(): ?string
    {
        return null;
    }

    protected function kind(): string
    {
        return 'info';
    }

    public function via($notifiable)
    {
        if ($this->category && $notifiable->memberProfile) {
            $prefs = $notifiable->memberProfile->prefs();
            if (empty($prefs[$this->category])) {
                return [];
            }
        }

        return ['database'];
    }

    public function toArray($notifiable)
    {
        return ['title' => $this->title(), 'body' => $this->body(), 'url' => $this->url(), 'kind' => $this->kind()];
    }
}
