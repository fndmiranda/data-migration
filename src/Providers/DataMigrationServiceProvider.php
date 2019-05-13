<?php

namespace Fndmiranda\DataMigration\Providers;

use Fndmiranda\DataMigration\Console\StatusCommand;
use Fndmiranda\DataMigration\DataMigration;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class DataMigrationServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        App::bind('data-migration', DataMigration::class);

        $this->commands([
            StatusCommand::class,
        ]);
    }
}
