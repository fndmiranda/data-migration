<?php

namespace Fndmiranda\DataMigrate\Providers;

use Fndmiranda\DataMigrate\Console\StatusCommand;
use Fndmiranda\DataMigrate\DataMigrate;
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
        App::bind('data-migrate', DataMigrate::class);

        $this->commands([
            StatusCommand::class,
        ]);
    }
}
