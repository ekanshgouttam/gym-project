<?php

namespace App\Http\Controllers\GymAdmin;

use App\Http\Controllers\Controller;
use App\Models\DietPlan;
use App\Models\MemberProfile;
use App\Models\User;
use App\Models\WorkoutPlan;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Trainers: public profile, and which members each trainer looks after. */
class TrainerController extends Controller
{
    public function index(Request $request)
    {
        $gymId = $request->user()->gym_id;
        $trainers = User::role(Roles::TRAINER)->where('gym_id', $gymId)->with('trainerProfile')->orderBy('name')->get();
        $assigned = MemberProfile::whereIn('trainer_id', $trainers->pluck('id'))->groupBy('trainer_id')->selectRaw('trainer_id, COUNT(*) as n')->pluck('n', 'trainer_id');
        $workouts = WorkoutPlan::whereIn('trainer_id', $trainers->pluck('id'))->where('is_active', true)->groupBy('trainer_id')->selectRaw('trainer_id, COUNT(*) as n')->pluck('n', 'trainer_id');

        return view('gym-admin.trainers.index', [
            'trainers' => $trainers,
            'assigned' => $assigned,
            'workouts' => $workouts,
            'unassigned' => MemberProfile::whereNull('trainer_id')->whereHas('user', function ($q) use ($gymId) { $q->where('gym_id', $gymId)->where('status', 'active'); })->count(),
        ]);
    }

    public function edit(Request $request, User $trainer)
    {
        $this->authorizeTrainer($request, $trainer);

        return view('gym-admin.trainers.edit', [
            'trainer' => $trainer->load('trainerProfile'),
            'members' => User::role(Roles::MEMBER)->where('gym_id', $trainer->gym_id)->where('status', 'active')
                ->with('memberProfile:id,user_id,member_code,trainer_id', 'memberProfile.trainer:id,name')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $trainer)
    {
        $this->authorizeTrainer($request, $trainer);
        $memberIds = User::role(Roles::MEMBER)->where('gym_id', $trainer->gym_id)->pluck('id')->all();

        $data = $request->validate([
            'specialty' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'certifications' => ['nullable', 'string', 'max:255'],
            'members' => ['array'],
            'members.*' => [Rule::in($memberIds)],
        ]);

        $trainer->trainerProfile()->updateOrCreate([], [
            'specialty' => $data['specialty'] ?? null,
            'bio' => $data['bio'] ?? null,
            'experience_years' => (int) ($data['experience_years'] ?? 0),
            'certifications' => $data['certifications'] ?? null,
            'is_public' => $request->boolean('is_public'),
        ]);

        // Sync assignments: chosen members get this trainer; members unticked here lose them.
        $chosen = $data['members'] ?? [];
        MemberProfile::where('trainer_id', $trainer->id)->whereNotIn('user_id', $chosen)->update(['trainer_id' => null]);
        MemberProfile::whereIn('user_id', $chosen)->update(['trainer_id' => $trainer->id]);

        return redirect()->route('gym-admin.trainers.index')->with('status', "{$trainer->name}'s profile and members were saved.");
    }

    private function authorizeTrainer(Request $request, User $trainer): void
    {
        abort_unless($trainer->gym_id === $request->user()->gym_id && $trainer->hasRole(Roles::TRAINER), 404);
    }
}
