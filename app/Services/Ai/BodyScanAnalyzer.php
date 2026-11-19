<?php

namespace App\Services\Ai;

use App\Models\AiUsageLog;
use App\Models\BodyScan;
use App\Models\PlatformSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Claude vision for the AI Body Scanner: a visual progress report for one scan, and a
 * before/after comparison of two scans. Reports describe what is visible (posture,
 * proportions, shape, visible definition); they never claim body-fat %, muscle mass or
 * any other medical measurement.
 *
 * Calls the Messages API over HTTP because the official Anthropic PHP SDK requires
 * PHP 8.1+ and this deployment runs PHP 7.4. Uses base64 image blocks, structured
 * output (output_config.format) and the server-side refusal fallback.
 */
class BodyScanAnalyzer
{
    private const ENDPOINT = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2026-10-21';

    private const GROUND_RULES = <<<'TXT'
Ground rules for every report:
- Describe only what is visible in the photos: posture, alignment, proportions, overall shape,
  visible muscle definition and general appearance.
- Never state or imply a body-fat percentage, muscle mass, BMI, weight, or any medical or
  diagnostic measurement. Photo-based impressions are not measurements, and the member must
  not read them as one. Use qualitative words (e.g. "more visible definition in the shoulders").
- Lighting, camera distance, angle, pose, clothing and time of day change how a body looks.
  Say when they limit what you can conclude, and never invent change you cannot see.
- Write for the member: encouraging but direct, plain language. Recommendations are concrete
  training, nutrition or habit actions tied to their goal, plus how to take more consistent photos.
TXT;

    private const SCAN_PROMPT = <<<'TXT'
You write the visual progress report for one body scan in a gym's member app. The member
uploaded front, side and back photos in fitted gym clothing. If the photos do not clearly show
one person's physique (wrong subject, too dark, heavily cropped, baggy clothing), set
image_quality_ok to false, explain what to fix in image_quality_issue, and keep the other
fields minimal.
TXT;

    private const COMPARE_PROMPT = <<<'TXT'
You compare two body scans of the same gym member: BEFORE (earlier) and AFTER (later), each
with front, side and back photos. Report the visible changes area by area. First judge whether
the two sets are comparable (similar lighting, distance, angle, pose and clothing) and explain
any differences in comparability_notes; if they are not comparable, say so and keep conclusions
tentative rather than guessing.
TXT;

    /** Needs an API key, and the Super Admin can switch AI scans off platform-wide in Settings. */
    public function enabled(): bool
    {
        return filled(config('services.anthropic.api_key')) && PlatformSetting::get('ai_scans_enabled');
    }

    /** Visual progress report for a single scan. */
    public function analyze(BodyScan $scan, ?BodyScan $previous = null): array
    {
        $content = $this->photoBlocks($scan, 'This scan');
        $content[] = ['type' => 'text', 'text' => $this->memberContext($scan) . $this->previousContext($scan, $previous)];

        return $this->call(self::SCAN_PROMPT, $content, self::scanSchema(), 'scan_analysis', $scan);
    }

    /** Before/after comparison of two scans. */
    public function compare(BodyScan $from, BodyScan $to, array $facts): array
    {
        $content = array_merge(
            $this->photoBlocks($from, 'BEFORE (' . $from->created_at->toDateString() . ')'),
            $this->photoBlocks($to, 'AFTER (' . $to->created_at->toDateString() . ')')
        );

        $lines = [$this->memberContext($to), '', 'Facts recorded by the gym (not from the photos):'];
        $lines[] = '- Days between scans: ' . $from->created_at->diffInDays($to->created_at);
        $lines[] = '- Weight: ' . ($from->weight_kg ?? '?') . ' kg → ' . ($to->weight_kg ?? '?') . ' kg';
        foreach ($facts['measurements'] ?? [] as $label => $delta) {
            $lines[] = "- {$label}: {$delta['from']} cm → {$delta['to']} cm";
        }
        $lines[] = '';
        $lines[] = 'Use these facts as context only; base visual observations on the photos.';
        $content[] = ['type' => 'text', 'text' => implode("\n", $lines)];

        return $this->call(self::COMPARE_PROMPT, $content, self::comparisonSchema(), 'scan_comparison', $to);
    }

    private function call(string $prompt, array $content, array $schema, string $kind, BodyScan $scan): array
    {
        $response = $this->send($prompt, $content, $schema);

        try {
            $data = $this->parse($response);
            $this->logUsage($response, $kind, $scan, null);

            return $data;
        } catch (RuntimeException $e) {
            $this->logUsage($response, $kind, $scan, $e->getMessage());
            throw $e;
        }
    }

    /** Records tokens and estimated cost of every answered API call (Super Admin → AI usage). Never breaks the scan. */
    private function logUsage($response, string $kind, BodyScan $scan, ?string $error): void
    {
        try {
            $usage = (array) $response->json('usage', []);
            $model = $response->json('model') ?: config('services.anthropic.model');
            $input = (int) ($usage['input_tokens'] ?? 0);
            $output = (int) ($usage['output_tokens'] ?? 0);
            $write = (int) ($usage['cache_creation_input_tokens'] ?? 0);
            $read = (int) ($usage['cache_read_input_tokens'] ?? 0);
            $user = $scan->user;

            AiUsageLog::create([
                'gym_id' => optional($user)->gym_id,
                'user_id' => optional($user)->id,
                'kind' => $kind,
                'model' => $model,
                'input_tokens' => $input,
                'output_tokens' => $output,
                'cache_write_tokens' => $write,
                'cache_read_tokens' => $read,
                'cost_usd' => AiUsageLog::estimateCost($model, $input, $output, $write, $read),
                'success' => $error === null,
                'error' => $error ? mb_substr($error, 0, 250) : null,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function send(string $prompt, array $content, array $schema)
    {
        if (! $this->enabled()) {
            throw new RuntimeException('AI analysis is not configured (ANTHROPIC_API_KEY missing).');
        }

        try {
            $response = Http::timeout((int) config('services.anthropic.timeout', 180))
                ->withHeaders([
                    'x-api-key' => config('services.anthropic.api_key'),
                    'anthropic-version' => self::API_VERSION,
                    // If the primary model declines, the API re-runs the request on a fallback model in the same call.
                    'anthropic-beta' => 'server-side-fallback-2026-07-01',
                ])
                ->post(self::ENDPOINT, [
                    'model' => config('services.anthropic.model', 'claude-opus-5-5'),
                    'max_tokens' => 16000,
                    'fallbacks' => 'default',
                    'system' => $prompt . "\n\n" . self::GROUND_RULES,
                    'output_config' => [
                        'effort' => config('services.anthropic.effort', 'medium'),
                        'format' => ['type' => 'json_schema', 'schema' => $schema],
                    ],
                    'messages' => [['role' => 'user', 'content' => $content]],
                ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Could not reach the AI service. Please try again shortly.', 0, $e);
        }

        if ($response->status() === 429 || $response->status() >= 500) {
            throw new RuntimeException('The AI service is busy right now. Please try again in a few minutes.');
        }
        if (! $response->successful()) {
            throw new RuntimeException('AI request failed (' . $response->status() . '): ' . $response->json('error.message', $response->body()));
        }

        return $response;
    }

    /** Turns an answered (billed) response into report data, or throws a member-friendly error. */
    private function parse($response): array
    {
        $stopReason = $response->json('stop_reason');
        if ($stopReason === 'refusal') {
            throw new RuntimeException('These photos could not be analysed. Please retake them in fitted gym clothing with good lighting.');
        }
        if ($stopReason === 'max_tokens') {
            throw new RuntimeException('The analysis was cut short. Please try again.');
        }

        $text = collect($response->json('content', []))->firstWhere('type', 'text')['text'] ?? null;
        $data = $text ? json_decode($text, true) : null;
        if (! is_array($data) || ! isset($data['summary'])) {
            throw new RuntimeException('The AI returned an unreadable result. Please try again.');
        }

        $data['_model'] = $response->json('model');

        return $data;
    }

    private function photoBlocks(BodyScan $scan, string $label): array
    {
        $blocks = [];
        foreach (BodyScan::VIEWS as $view) {
            $path = $scan->photoPath($view);
            if (! $path || ! Storage::disk('local')->exists($path)) {
                continue;
            }
            $blocks[] = ['type' => 'text', 'text' => "{$label} — {$view} view:"];
            $blocks[] = [
                'type' => 'image',
                'source' => ['type' => 'base64', 'media_type' => 'image/jpeg', 'data' => base64_encode(Storage::disk('local')->get($path))],
            ];
        }

        return $blocks;
    }

    private function memberContext(BodyScan $scan): string
    {
        $profile = optional($scan->user()->with('memberProfile')->first())->memberProfile;

        return implode("\n", [
            'Member context:',
            '- Age: ' . (optional($profile)->age() ?? 'unknown'),
            '- Sex: ' . (optional($profile)->gender ?? 'not specified'),
            '- Goal: ' . ($profile ? $profile->goalLabel() : 'general fitness'),
        ]);
    }

    private function previousContext(BodyScan $scan, ?BodyScan $previous): string
    {
        if (! $previous || ! $previous->analysis) {
            return "\n\nThis is the member's first analysed scan; set changes_since_last to an empty string.";
        }

        return "\n\nPrevious scan (" . $previous->created_at->toDateString() . ', ' . $previous->created_at->diffInDays($scan->created_at) . " days earlier) summary:\n" . $previous->summary;
    }

    public static function scanSchema(): array
    {
        $definition = ['type' => 'string', 'enum' => ['low', 'moderate', 'high', 'unclear']];
        $strings = ['type' => 'array', 'items' => ['type' => 'string']];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => [
                'image_quality_ok', 'image_quality_issue', 'posture', 'proportions', 'body_shape', 'visual_leanness',
                'visible_definition', 'observations', 'strengths', 'focus_areas', 'recommendations',
                'visual_score', 'changes_since_last', 'photo_consistency_tips', 'summary',
            ],
            'properties' => [
                'image_quality_ok' => ['type' => 'boolean'],
                'image_quality_issue' => ['type' => 'string', 'description' => 'Empty when image_quality_ok is true.'],
                'posture' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['summary', 'observations'],
                    'properties' => ['summary' => ['type' => 'string'], 'observations' => $strings],
                ],
                'proportions' => ['type' => 'string', 'description' => 'Visible shoulder, waist and hip proportions.'],
                'body_shape' => ['type' => 'string'],
                'visual_leanness' => ['type' => 'string', 'enum' => array_keys(BodyScan::LEANNESS), 'description' => 'Qualitative impression only, never a percentage.'],
                'visible_definition' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['upper_body', 'core', 'lower_body'],
                    'properties' => ['upper_body' => $definition, 'core' => $definition, 'lower_body' => $definition],
                ],
                'observations' => $strings,
                'strengths' => $strings,
                'focus_areas' => $strings,
                'recommendations' => $strings,
                'visual_score' => ['type' => 'integer', 'description' => '1-100 relative visual condition score for tracking this member over time; not a body-composition measurement.'],
                'changes_since_last' => ['type' => 'string'],
                'photo_consistency_tips' => $strings,
                'summary' => ['type' => 'string', 'description' => 'Two or three sentences for the member.'],
            ],
        ];
    }

    public static function comparisonSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['comparable', 'comparability_notes', 'changes', 'posture_change', 'overall_direction', 'summary', 'recommendations'],
            'properties' => [
                'comparable' => ['type' => 'boolean'],
                'comparability_notes' => ['type' => 'string'],
                'changes' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['area', 'observation', 'direction'],
                        'properties' => [
                            'area' => ['type' => 'string'],
                            'observation' => ['type' => 'string'],
                            'direction' => ['type' => 'string', 'enum' => ['improved', 'similar', 'regressed', 'unclear']],
                        ],
                    ],
                ],
                'posture_change' => ['type' => 'string'],
                'overall_direction' => ['type' => 'string', 'enum' => ['noticeable_progress', 'some_progress', 'little_visible_change', 'regression', 'unclear']],
                'summary' => ['type' => 'string'],
                'recommendations' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
        ];
    }
}
