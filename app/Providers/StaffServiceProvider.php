<?php

namespace Modules\Staff\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Modules\Staff\Classes\Services\StaffAccountService;
use Modules\Staff\Classes\Services\StaffAssignmentService;
use Modules\Staff\Classes\Services\StaffCredentialService;
use Modules\Staff\Classes\Services\StaffSearchService;
use Modules\Staff\Classes\Services\StaffService;
use Modules\Staff\Models\Staff;
use Modules\Staff\Policies\StaffPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class StaffServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Staff';

    protected string $nameLower = 'staff';

    protected array $providers = [
        RouteServiceProvider::class,
        EventServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();
        $this->registerViews();
        Gate::policy(Staff::class, StaffPolicy::class);
        $this->registerServices();
    }

    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\'.$this->name.'\\View\\Components', $this->nameLower);
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
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
}
