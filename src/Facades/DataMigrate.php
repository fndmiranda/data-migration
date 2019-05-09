<?php

namespace Fndmiranda\DataMigrate\Facades;

use Illuminate\Support\Facades\Facade;

class DataMigrate extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'data-migrate';
    }
}