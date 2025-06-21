<?php

namespace App\Providers;

use App\Models\App;
use App\Models\Installation;
use App\Policies\AppPolicy;
use App\Policies\InstallationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        App::class => AppPolicy::class,
        Installation::class => InstallationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
