<?php

namespace App\Notifications;

use App\Models\BodyScan;
use App\Models\Membership;
use App\Models\User;

class BodyScanCompleted extends AppNotification
{
    protected $category = 'scans';

    private $scan;

    public function __construct(BodyScan $scan)
    {
        $this->scan = $scan;
    }

    protected function title(): string
    {
        return 'Your AI body scan is ready';
    }

    protected function body(): string
    {
        return 'Your visual progress report is ready (AI visual score ' . $this->scan->physique_score . '/100).';
    }

    protected function url(): ?string
    {
        return route('member.scanner.show', $this->scan);
    }

    protected function kind(): string
    {
        return 'success';
    }
}
