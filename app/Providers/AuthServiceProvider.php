<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::before(function (User $user): ?bool {
            return $user->hasRole('system-administrator') ? true : null;
        });

        Gate::define('view-audit', fn (User $user): bool => $user->hasPermission('audit.view'));
        Gate::define('manage-users', fn (User $user): bool => $user->hasPermission('users.manage'));
    }
}
