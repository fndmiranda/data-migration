<?php

namespace Fndmiranda\DataMigrate\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface DataMigrate
{
    /**
     * Get the model being used by the data-migrate.
     *
     * @return string|Model
     */
    public function model();

    /**
     * Get the data being used by the data-migrate.
     *
     * @return array|Collection
     */
    public function data();

    /**
     * Get the data options being used by the data-migrate.
     *
     * @return array|Collection
     */
    public function options();
}
