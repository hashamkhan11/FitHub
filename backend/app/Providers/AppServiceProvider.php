<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('view-insight', fn (User $user) => $user->role === 'owner');
        Gate::define('manage-plans', fn (User $user) => $user->role === 'owner');
        Gate::define('view-plans', fn (User $user) => in_array($user->role, ['owner', 'staff']));
        Gate::define('manage-staff', fn (User $user) => $user->role === 'owner');
        Gate::define('manage-business', fn (User $user) => $user->role === 'owner');

        Gate::define('manage-trainers', fn (User $user) => $user->role === 'owner');
        Gate::define('view-trainers', fn (User $user) => in_array($user->role, ['owner', 'staff']));

        Gate::define('manage-members', fn (User $user) => in_array($user->role, ['owner', 'staff']));
        Gate::define('view-members', fn (User $user) => in_array($user->role, ['owner', 'staff', 'trainer']));

        Gate::define('manage-payments', fn (User $user) => in_array($user->role, ['owner', 'staff']));

        Gate::define('checkin-attendance', fn (User $user) => in_array($user->role, ['owner', 'staff']));
        Gate::define('view-attendance', fn (User $user) => in_array($user->role, ['owner', 'staff', 'trainer']));

        Gate::define('manage-classes', fn (User $user) => $user->role === 'owner');
        Gate::define('view-classes', fn (User $user) => in_array($user->role, ['owner', 'staff', 'trainer']));

        Gate::define('manage-bookings', fn (User $user) => in_array($user->role, ['owner', 'staff']));
        Gate::define('view-bookings', fn (User $user) => in_array($user->role, ['owner', 'staff', 'trainer']));

        Gate::define('view-activity', fn (User $user) => $user->role === 'owner');

        Gate::define('manage-lock-devices', fn (User $user) => $user->role === 'owner');
        Gate::define('trigger-lock', fn (User $user) => in_array($user->role, ['owner', 'staff']));
    }
}
