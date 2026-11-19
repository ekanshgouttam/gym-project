<?php

namespace App\Jobs;

use App\Models\ScanComparison;
use App\Services\Ai\BodyScanAnalyzer;
use App\Support\ScanComparisonFacts;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GenerateScanComparison implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 300;

    /** @var ScanComparison */
    public $comparison;

    public function __construct(ScanComparison $comparison)
    {
        $this->comparison = $comparison;
    }

    public function handle(BodyScanAnalyzer $analyzer)
    {
        $c = $this->comparison->fresh(['from', 'to']);
        if (! $c || $c->status !== 'pending') {
            return;
        }

        try {
            $facts = ScanComparisonFacts::for($c->from, $c->to);
            $result = $analyzer->compare($c->from, $c->to, $facts);
        } catch (RuntimeException $e) {
            Log::warning('Scan comparison failed', ['comparison' => $c->id, 'error' => $e->getMessage()]);
            $c->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 250)]);

            return;
        }

        $c->update(['status' => 'completed', 'analysis' => $result, 'summary' => $result['summary'], 'error' => null]);
    }
}
