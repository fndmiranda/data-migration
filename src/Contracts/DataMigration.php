<?php

namespace Fndmiranda\DataMigration\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface DataMigration
{
    /**
     * Get the model being used by the data-migration.
     *
     * @return string|Model
     */
    public function model();

    /**
     * Get the data being used by the data-migration.
     *
     * @return array|Collection
     */
    public function data();

    /**
     * Get the data options being used by the data-migration.
     *
     * @return array|Collection
     */
    public function options();
}
