<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TestingDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Loads the full testing data, then opens every page each role can reach by following
 * links (GET only). Catches errors that only appear once real data is on screen, such as
 * a missing model relation in a list.
 */
class SeededPagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    private const START = [
        'admin@gym.test' => '/gym-admin',
        'koramangala.admin@gym.test' => '/gym-admin',
        'superadmin@gym.test' => '/super-admin',
        'trainer@gym.test' => '/trainer',
        'member@gym.test' => '/member',
        'expiring@gym.test' => '/member',
    ];

    /** Links not to follow: actions, file downloads, private media and pages that record visits. */
    private const SKIP = '#logout|export|download|\.csv|template|/media/|/join/|/notifications/[0-9a-f-]{8,}#';

    public function test_every_reachable_page_renders_with_seeded_data()
    {
        Storage::fake('local');
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(TestingDataSeeder::class);

        $report = [];
        foreach (self::START as $email => $start) {
            $this->actingAs(User::where('email', $email)->firstOrFail());
            $report[] = $this->crawl($email, $start);
            $this->app['auth']->forgetGuards();
        }
        $report[] = $this->crawl('(visitor)', '/');

        $errors = collect($report)->pluck('errors')->flatten()->all();
        $this->assertSame([], $errors, "Pages that failed:\n" . implode("\n", $errors));
        foreach ($report as $r) {
            $this->assertGreaterThan(3, $r['pages'], "{$r['who']} reached too few pages — the start page may be broken.");
        }
    }

    private function crawl(string $who, string $start): array
    {
        $seen = [];
        $queue = [$start];
        $errors = [];

        while ($queue && count($seen) < 300) {
            $path = array_shift($queue);
            if (isset($seen[$path]) || preg_match(self::SKIP, $path)) {
                continue;
            }
            $seen[$path] = true;

            $response = $this->get($path);
            $status = $response->getStatusCode();
            if ($status >= 500) {
                $e = $response->exception;
                $errors[] = "[$who] $status $path — " . ($e ? get_class($e) . ': ' . $e->getMessage() : 'server error');
                continue;
            }
            if ($status !== 200) {
                continue;
            }

            preg_match_all('#href="(?:' . preg_quote(url('/'), '#') . ')?(/[^"\#]*)"#', (string) $response->getContent(), $m);
            foreach ($m[1] as $href) {
                $href = html_entity_decode($href);
                // One extra page of each paginated list is enough.
                if (preg_match('#[?&]page=(\d+)#', $href, $p) && $p[1] !== '2') {
                    continue;
                }
                $queue[] = $href;
            }
        }

        return ['who' => $who, 'pages' => count($seen), 'errors' => $errors];
    }
}
