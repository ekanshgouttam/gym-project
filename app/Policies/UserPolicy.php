<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Roles;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Super Admin: manages everyone (granted via Gate::before).
 * Gym Admin:   manages trainers / members inside their own gym only,
 *              and only for the role types they hold the permission for.
 * Trainer / Member: cannot manage users.
 */
class UserPolicy
{
    use HandlesAuthorization;

    private const ROLE_PERMISSION = [
        Roles::TRAINER => 'manage trainers',
        Roles::MEMBER => 'manage members',
    ];

    public function viewAny(User $actor): bool
    {
        return $actor->hasAnyPermission(['manage trainers', 'manage members']);
    }

    public function create(User $actor): bool
    {
        return $this->viewAny($actor);
    }

    /** May the actor assign this role to a user? */
    public function assignRole(User $actor, string $role): bool
    {
        if ($actor->isSuperAdmin()) {
            return in_array($role, Roles::ALL, true);
        }

        $permission = self::ROLE_PERMISSION[$role] ?? null;

        return $permission !== null && $actor->can($permission);
    }

    public function view(User $actor, User $target): bool
    {
        return $this->manages($actor, $target);
    }

    public function update(User $actor, User $target): bool
    {
        return $this->manages($actor, $target);
    }

    public function changeStatus(User $actor, User $target): bool
    {
        return $actor->id !== $target->id && $this->manages($actor, $target);
    }

    public function delete(User $actor, User $target): bool
    {
        return $actor->id !== $target->id && $this->manages($actor, $target);
    }

    private function manages(User $actor, User $target): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        if ($actor->gym_id === null || $actor->gym_id !== $target->gym_id) {
            return false;
        }

        return $this->assignRole($actor, (string) $target->primaryRole());
    }
}
