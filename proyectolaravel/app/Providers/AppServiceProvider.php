<?php

namespace App\Providers;

use App\Models\User;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Repositories\EloquentTicketRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            TicketRepositoryInterface::class,
            EloquentTicketRepository::class,
        );
    }

    public function boot(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            if (
                $user->hasRole('admin')
                && ! in_array($ability, [
                    'update',
                    'delete',
                    'assign',
                    'close',
                ], true)
            ) {
                return true;
            }

            return null;
        });
    }
}
