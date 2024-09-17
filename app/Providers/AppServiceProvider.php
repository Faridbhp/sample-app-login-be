<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Responses\ResetPasswordViewResponse;
use Laravel\Fortify\Contracts\ResetPasswordViewResponse as ResetPasswordViewResponseContract;
use App\Actions\Fortify\ResetsUserPasswords;
use Laravel\Fortify\Contracts\ResetsUserPasswords as ResetsUserPasswordsContract;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->bind(ResetPasswordViewResponseContract::class, ResetPasswordViewResponse::class);
        $this->app->bind(ResetsUserPasswordsContract::class, ResetsUserPasswords::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
