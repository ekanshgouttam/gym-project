<?php

namespace App\Http\Controllers\GymAdmin;

use App\Http\Controllers\Controller;
use App\Models\DietPlan;
use App\Models\User;
use App\Models\WorkoutPlan;
use App\Notifications\PlanAssigned;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Workout and diet plan builder for gym owners and trainers.
 * Exercises and meal items are typed one per line, so plans are quick to write on a phone:
 *   workout line: "Bench press | 4 | 8-10 | 90s"   (name | sets | reps | rest)
 *   diet line:    "2 eggs, 1 toast"
 * One plan can be assigned to several members at once; each gets their own copy, and their
 * previous active plan of the same kind is archived.
 */
class PlanController extends Controller
{
    public const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    private const KINDS = ['workout' => WorkoutPlan::class, 'diet' => DietPlan::class];

    public function index(Request $request)
    {
        $gymId = $request->user()->gym_id;
        $kind = $request->query('kind', 'workout') === 'diet' ? 'diet' : 'workout';
        $model = self::KINDS[$kind];

        $plans = $model::with('trainer:id,name')
            ->whereIn('member_id', $this->memberQuery($request)->select('id'))
            ->when($request->filled('member'), function ($q) use ($request) { $q->where('member_id', $request->member); })
            ->when(! $request->boolean('archived'), function ($q) { $q->where('is_active', true); })
            ->latest()->paginate(20)->withQueryString();
        $members = User::whereIn('id', $plans->pluck('member_id'))->get(['id', 'name'])->keyBy('id');

        $memberIds = $this->memberQuery($request)->pluck('id');
        $withPlan = $model::whereIn('member_id', $memberIds)->where('is_active', true)->distinct()->count('member_id');

        return view('gym-admin.plans.index', [
            'kind' => $kind,
            'plans' => $plans,
            'members' => $members,
            'coverage' => ['with' => $withPlan, 'total' => $memberIds->count()],
            'allMembers' => $this->memberQuery($request)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request, string $kind)
    {
        $this->kind($kind);
        $class = self::KINDS[$kind];
        $template = null;
        if ($request->filled('from')) {
            $template = self::KINDS[$kind]::whereIn('member_id', $this->memberQuery($request)->select('id'))->find($request->query('from'));
        }

        return view('gym-admin.plans.form', [
            'kind' => $kind,
            'plan' => $template ? $template->replicate() : new $class(),
            'members' => $this->memberQuery($request)->with('memberProfile:id,user_id,member_code,trainer_id')->orderBy('name')->get(['id', 'name']),
            'selected' => array_filter([(int) $request->query('member')]),
            'days' => self::DAYS,
        ]);
    }

    public function store(Request $request, string $kind)
    {
        $this->kind($kind);
        $data = $this->validated($request, $kind);
        $memberIds = $request->validate([
            'member_ids' => ['required', 'array', 'min:1'],
            'member_ids.*' => [Rule::in($this->memberQuery($request)->pluck('id')->all())],
        ], ['member_ids.required' => 'Choose at least one member.'])['member_ids'];

        $model = self::KINDS[$kind];
        DB::transaction(function () use ($model, $memberIds, $data, $request) {
            foreach ($memberIds as $id) {
                $model::where('member_id', $id)->where('is_active', true)->update(['is_active' => false]);
                $model::create($data + ['member_id' => $id, 'trainer_id' => $request->user()->id, 'is_active' => true]);
            }
        });

        User::whereIn('id', $memberIds)->get()->each(function ($m) use ($kind, $data) { $m->notify(new PlanAssigned($kind, $data['title'])); });

        return redirect()->route('gym-admin.plans.index', ['kind' => $kind])
            ->with('status', ucfirst($kind) . " plan “{$data['title']}” assigned to " . count($memberIds) . ' ' . \Illuminate\Support\Str::plural('member', count($memberIds)) . '.');
    }

    public function edit(Request $request, string $kind, int $plan)
    {
        $this->kind($kind);
        $record = $this->find($request, $kind, $plan);

        return view('gym-admin.plans.form', [
            'kind' => $kind,
            'plan' => $record,
            'member' => User::find($record->member_id),
            'members' => collect(),
            'selected' => [],
            'days' => self::DAYS,
        ]);
    }

    public function update(Request $request, string $kind, int $plan)
    {
        $this->kind($kind);
        $record = $this->find($request, $kind, $plan);
        $record->update($this->validated($request, $kind));

        return redirect()->route('gym-admin.plans.index', ['kind' => $kind])->with('status', 'Plan updated.');
    }

    public function archive(Request $request, string $kind, int $plan)
    {
        $this->kind($kind);
        $record = $this->find($request, $kind, $plan);
        $record->update(['is_active' => ! $record->is_active]);

        return back()->with('status', $record->is_active ? 'Plan reactivated.' : 'Plan archived.');
    }

    private function validated(Request $request, string $kind): array
    {
        if ($kind === 'workout') {
            $data = $request->validate([
                'title' => ['required', 'string', 'max:120'],
                'goal' => ['nullable', 'string', 'max:120'],
                'starts_on' => ['nullable', 'date'],
                'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
                'notes' => ['nullable', 'string', 'max:2000'],
                'days' => ['array'],
                'days.*.day' => ['nullable', Rule::in(self::DAYS)],
                'days.*.focus' => ['nullable', 'string', 'max:80'],
                'days.*.exercises' => ['nullable', 'string', 'max:3000'],
            ]);
            $days = collect($data['days'] ?? [])->map(function ($d) {
                $exercises = collect(preg_split('/\r?\n/', (string) ($d['exercises'] ?? '')))->map(function ($line) {
                    $parts = array_map('trim', explode('|', $line));
                    if ($parts[0] === '') {
                        return null;
                    }

                    return ['name' => mb_substr($parts[0], 0, 80), 'sets' => $parts[1] ?? '', 'reps' => $parts[2] ?? '', 'rest' => $parts[3] ?? ''];
                })->filter()->values()->all();

                return ['day' => $d['day'] ?? '', 'focus' => trim($d['focus'] ?? ''), 'exercises' => $exercises];
            })->filter(function ($d) { return $d['day'] && ($d['focus'] || $d['exercises']); })->values()->all();

            if (! $days) {
                back()->withInput()->withErrors(['days' => 'Add at least one training day with exercises.'])->throwResponse();
            }
            $data['days'] = $days;

            return $data;
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'daily_calories' => ['nullable', 'integer', 'min:500', 'max:8000'],
            'protein' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'carbs' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'fat' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'meals' => ['array'],
            'meals.*.time' => ['nullable', 'string', 'max:20'],
            'meals.*.name' => ['nullable', 'string', 'max:60'],
            'meals.*.items' => ['nullable', 'string', 'max:2000'],
        ]);
        $meals = collect($data['meals'] ?? [])->map(function ($m) {
            $items = collect(preg_split('/\r?\n/', (string) ($m['items'] ?? '')))->map(function ($i) { return trim($i); })->filter()->values()->all();

            return ['time' => trim($m['time'] ?? ''), 'name' => trim($m['name'] ?? ''), 'items' => $items];
        })->filter(function ($m) { return $m['name'] || $m['items']; })->values()->all();

        if (! $meals) {
            back()->withInput()->withErrors(['meals' => 'Add at least one meal.'])->throwResponse();
        }

        return [
            'title' => $data['title'],
            'daily_calories' => $data['daily_calories'] ?? null,
            'macros' => array_filter(['protein' => $data['protein'] ?? null, 'carbs' => $data['carbs'] ?? null, 'fat' => $data['fat'] ?? null], function ($v) { return $v !== null; }) ?: null,
            'meals' => $meals,
            'notes' => $data['notes'] ?? null,
        ];
    }

    /** Members a user can plan for: everyone at their gym. */
    private function memberQuery(Request $request)
    {
        return User::role(Roles::MEMBER)->where('gym_id', $request->user()->gym_id)->where('status', 'active');
    }

    private function find(Request $request, string $kind, int $id)
    {
        return self::KINDS[$kind]::whereIn('member_id', User::role(Roles::MEMBER)->where('gym_id', $request->user()->gym_id)->select('id'))->findOrFail($id);
    }

    private function kind(string $kind): void
    {
        abort_unless(isset(self::KINDS[$kind]), 404);
    }
}
