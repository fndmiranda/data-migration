<?php

namespace Fndmiranda\DataMigration\Facades;

use Illuminate\Support\Facades\Facade;

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
