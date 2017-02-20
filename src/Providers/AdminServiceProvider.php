<?php

namespace Kevinbai\Admin\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../../views', 'admin');
        $this->loadTranslationsFrom(__DIR__.'/../../lang/', 'admin');


        $this->publishes([__DIR__.'/../../controllers/HomeController.php'
            => app_path('Http/Controllers/HomeController.php')],'laravel-admin-components');
        $this->publishes([__DIR__.'/../../controllers/ExampleController.php'
            => app_path('Http/Controllers/ExampleController')],'laravel-admin-components');
        $this->publishes([__DIR__.'/../../bootstrap/admin.php' => app_path('../bootstrap/admin.php')], 'laravel-admin-components');
        $this->publishes([__DIR__.'/../../config/admin.php' => config_path('admin.php')], 'laravel-admin-components');
        $this->publishes([__DIR__.'/../../assets' => public_path('packages/admin')], 'laravel-admin-components');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->booting(function () {
            $loader = AliasLoader::getInstance();

            $loader->alias('Admin', \Kevinbai\Admin\Facades\Admin::class);
        });
    }
}
