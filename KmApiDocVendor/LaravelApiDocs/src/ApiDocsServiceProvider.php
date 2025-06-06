<?php
namespace KmApiDocVendor\LaravelApiDocs;

use Illuminate\Support\ServiceProvider;
use KmApiDocVendor\LaravelApiDocs\Console\GenerateDocsCommand;

class ApiDocsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'kmapidocs');

        $this->loadRoutesFrom(base_path('routes/web.php'));  // Loads main web.php
        $this->loadRoutesFrom(base_path('routes/api.php'));  
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');

        // Registering views from package
        $this->loadViewsFrom(__DIR__.'/resources/views', 'kmapidocs');

        // Publish views to the application's resource folder (optional)
        $this->publishes([
            __DIR__.'/resources/views' => resource_path('views/vendor/kmapidocs'),
        ], 'views');
    }

    public function register()
    {
        $this->commands([
            GenerateDocsCommand::class,
        ]);
    }
}
