<?php

namespace Fndmiranda\DataMigration\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\Collection status(string|\Fndmiranda\DataMigration\Contracts\DataMigration $dataMigrate, \Symfony\Component\Console\Helper\ProgressBar $progressBar = null)
 * @method static \Illuminate\Support\Collection diff(string|\Fndmiranda\DataMigration\Contracts\DataMigration $dataMigrate, \Symfony\Component\Console\Helper\ProgressBar $progressBar = null)
 * @method static \Illuminate\Support\Collection migrate(string|\Fndmiranda\DataMigration\Contracts\DataMigration $dataMigrate, \Symfony\Component\Console\Helper\ProgressBar $progressBar = null)
 * @method static \Illuminate\Support\Collection sync(string|\Fndmiranda\DataMigration\Contracts\DataMigration $dataMigrate, \Symfony\Component\Console\Helper\ProgressBar $progressBar = null)
 *
 * @see \Fndmiranda\DataMigration\DataMigration
 */
class DataMigration extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'data-migration';
    }
}
