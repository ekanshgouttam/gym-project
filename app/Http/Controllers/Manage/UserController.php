<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Gym;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * User management shared by Super Admin (all users, all gyms)
 * and Gym Admin (trainers + members of their own gym).
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);
        $actor = $request->user();
        $assignable = $this->assignableRoles($actor);

        $users = User::with('roles', 'gym')
            ->when(! $actor->isSuperAdmin(), function ($q) use ($actor, $assignable) {
                $q->where('gym_id', $actor->gym_id)->role($assignable);
            })
            ->when($request->filled('role') && in_array($request->role, $assignable, true), function ($q) use ($request) {
                $q->role($request->role);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->q . '%';
                $q->where(function ($w) use ($term) {
                    $w->where('name', 'like', $term)->orWhere('email', 'like', $term)->orWhere('phone', 'like', $term);
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('manage.users.index', compact('users', 'assignable'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', User::class);

        return view('manage.users.form', $this->formData($request->user(), new User()));
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);
        $actor = $request->user();
        $data = $this->validated($request, $actor);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'gym_id' => $data['gym_id'],
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'],
                'password' => Hash::make($data['password']),
            ]);
            $user->assignRole($data['role']);
        });

        return redirect()->route('manage.users.index')->with('status', "{$data['name']} was added as " . Roles::label($data['role']) . '.');
    }

    public function edit(Request $request, User $user)
    {
        $this->authorize('update', $user);

        return view('manage.users.form', $this->formData($request->user(), $user));
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);
        $actor = $request->user();
        $data = $this->validated($request, $actor, $user);

        DB::transaction(function () use ($user, $data, $actor) {
            $user->fill([
                'gym_id' => $data['gym_id'],
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
            ]);
            // An admin can't suspend or demote themselves out of access.
            if ($actor->id !== $user->id) {
                $user->status = $data['status'];
                $user->syncRoles([$data['role']]);
            }
            if (! empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }
            $user->save();
        });

        return redirect()->route('manage.users.index')->with('status', "{$user->name} was updated.");
    }

    public function toggleStatus(User $user)
    {
        $this->authorize('changeStatus', $user);
        $user->status = $user->isActive() ? 'suspended' : 'active';
        $user->save();

        return back()->with('status', "{$user->name} is now {$user->status}.");
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);
        $name = $user->name;
        $user->delete();

        return redirect()->route('manage.users.index')->with('status', "{$name} was removed.");
    }

    private function assignableRoles(User $actor): array
    {
        return array_values(array_filter(Roles::ALL, function ($role) use ($actor) {
            return $actor->can('assignRole', [User::class, $role]);
        }));
    }

    private function formData(User $actor, User $user): array
    {
        return [
            'user' => $user,
            'roles' => $this->assignableRoles($actor),
            'gyms' => $actor->isSuperAdmin() ? Gym::orderBy('name')->get(['id', 'name']) : collect(),
            'editingSelf' => $user->exists && $actor->id === $user->id,
        ];
    }

    private function validated(Request $request, User $actor, ?User $user = null): array
    {
        $editingSelf = $user && $actor->id === $user->id;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore(optional($user)->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => [$editingSelf ? 'nullable' : 'required', Rule::in($this->assignableRoles($actor))],
            'status' => [$editingSelf ? 'nullable' : 'required', Rule::in(['active', 'suspended'])],
            'gym_id' => [$actor->isSuperAdmin() ? 'nullable' : 'prohibited', 'exists:gyms,id'],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        // Gym Admins can only place users in their own gym.
        if (! $actor->isSuperAdmin()) {
            $data['gym_id'] = $actor->gym_id;
        }

        // Every role except Super Admin must belong to a gym.
        $role = $data['role'] ?? optional($user)->primaryRole();
        if ($role !== Roles::SUPER_ADMIN && empty($data['gym_id'])) {
            back()->withInput()->withErrors(['gym_id' => 'Please choose a gym for this user.'])->throwResponse();
        }
        if ($role === Roles::SUPER_ADMIN) {
            $data['gym_id'] = null;
        }

        return $data;
    }
}
