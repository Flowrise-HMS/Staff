<?php

namespace Modules\Staff\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Modules\Staff\Classes\Services\StaffAccountService;
use Modules\Staff\Classes\Services\StaffAssignmentService;
use Modules\Staff\Classes\Services\StaffCredentialService;
use Modules\Staff\Classes\Services\StaffSearchService;
use Modules\Staff\Classes\Services\StaffService;
use Modules\Staff\Models\Staff;
use Modules\Staff\Policies\StaffPolicy;

class StaffServiceProvider extends ServiceProvider
{
    protected string $name = 'Staff';

    protected string $nameLower = 'staff';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerServices();
    }

    public function register(): void
    {
        parent::register();
    }

    protected function registerServices(): void
    {
        $this->app->singleton(
            StaffService::class,
            fn () => new StaffService
        );

        $this->app->singleton(
            StaffCredentialService::class,
            fn () => new StaffCredentialService
        );

        $this->app->singleton(
            StaffAssignmentService::class,
            fn () => new StaffAssignmentService
        );

        $this->app->singleton(
            StaffSearchService::class,
            fn () => new StaffSearchService
        );

        $this->app->singleton(
            StaffAccountService::class,
            fn () => new StaffAccountService
        );
    }

    public function registerPolicies(): void
    {
        Gate::policy(Staff::class, StaffPolicy::class);
    }
}
