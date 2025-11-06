<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Log; // porque quieres excluirlo
use App\Models\Penalty;
use App\Models\User;
use App\Observers\GenericObserver;
use App\Observers\PenaltyObserver;
use Illuminate\Database\Eloquent\Model;

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
    public function boot()
    {
        User::observe(GenericObserver::class);
        Penalty::observe(GenericObserver::class);

        // Penalty::observe(PenaltyObserver::class);

        // Si quieres agregar más:
        // YourOtherModel::observe(GenericObserver::class);
    }
}
