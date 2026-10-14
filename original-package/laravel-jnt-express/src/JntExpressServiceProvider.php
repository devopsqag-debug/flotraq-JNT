<?php

namespace JntExpress;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use JntExpress\Console\CancelShipmentsCommand;
use JntExpress\Console\CreateShipmentsCommand;
use JntExpress\Console\PrintCommand;
use JntExpress\Console\QuoteCommand;
use JntExpress\Events\ShipmentStatusChanged;
use JntExpress\Listeners\SendShipmentStatusEmail;
use JntExpress\Support\Settings;

class JntExpressServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/jnt.php', 'jnt');

        $this->app->singleton(Settings::class);
        $this->app->singleton(JntManager::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'jnt');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if (config('jnt.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }

        Event::listen(ShipmentStatusChanged::class, SendShipmentStatusEmail::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../config/jnt.php' => config_path('jnt.php')], 'jnt-config');
            $this->publishes([__DIR__ . '/../resources/views' => resource_path('views/vendor/jnt')], 'jnt-views');
            $this->publishes([__DIR__ . '/../database/migrations' => database_path('migrations')], 'jnt-migrations');

            $this->commands([CreateShipmentsCommand::class, CancelShipmentsCommand::class, PrintCommand::class, QuoteCommand::class]);
        }
    }
}
