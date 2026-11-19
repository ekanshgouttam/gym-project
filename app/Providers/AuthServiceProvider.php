<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use App\Support\Roles;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();

        // Super Admin passes every permission check — except policy rules that
        // explicitly return false (e.g. a Super Admin cannot suspend themselves).
        Gate::before(function (User $user, string $ability) {
            if (in_array($ability, ['delete', 'changeStatus'], true)) {
                return null;
            }

            return $user->hasRole(Roles::SUPER_ADMIN) ? true : null;
        });
    }
}
