<?php

namespace Fndmiranda\DataMigrate\Providers;

use Fndmiranda\DataMigrate\Migrate;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class MigrateServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        App::bind('migrate', Migrate::class);
    }
}
