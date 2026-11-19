<?php

namespace App\Jobs;

use App\Models\BodyScan;
use App\Notifications\BodyScanCompleted;
use App\Services\Ai\BodyScanAnalyzer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AnalyzeBodyScan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 240;

    /** @var BodyScan */
    public $scan;

    public function __construct(BodyScan $scan)
    {
        $this->scan = $scan;
    }

    public function handle(BodyScanAnalyzer $analyzer)
    {
        $scan = $this->scan->fresh();
        if (! $scan || $scan->status !== 'pending' || $scan->isVoided()) {
            return;
        }
        $scan->increment('analysis_attempts');

        $previous = BodyScan::where('user_id', $scan->user_id)->accepted()
            ->where('status', 'completed')
            ->where('created_at', '<', $scan->created_at)
            ->latest('created_at')
            ->first();

        try {
            $result = $analyzer->analyze($scan, $previous);
        } catch (RuntimeException $e) {
            Log::warning('Body scan analysis failed', ['scan' => $scan->id, 'error' => $e->getMessage()]);
            $scan->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 250)]);

            return;
        }

        if (! $result['image_quality_ok']) {
            $scan->update([
                'status' => 'failed',
                'analysis' => $result,
                'error' => mb_substr($result['image_quality_issue'] ?: 'The photos were not clear enough to analyse.', 0, 250),
            ]);

            return;
        }

        $scan->update([
            'status' => 'completed',
            'analysis' => $result,
            'physique_score' => max(1, min(100, (int) $result['visual_score'])),
            'summary' => $result['summary'],
            'error' => null,
            'analyzed_at' => now(),
        ]);

        $scan->user->notify(new BodyScanCompleted($scan));
    }
}
