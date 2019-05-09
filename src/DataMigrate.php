<?php

namespace Fndmiranda\DataMigrate;

use Fndmiranda\DataMigrate\Traits\HasStatus;

class DataMigrate
{
    use HasStatus;

    /**
     * Constant representing an ok status.
     *
     * @var string
     */
    const OK = 'ok';

    /**
     * Constant representing a status to create.
     *
     * @var string
     */
    const CREATE = 'create';

    /**
     * Constant representing a status to update.
     *
     * @var string
     */
    const UPDATE = 'update';

    /**
     * Constant representing a status to delete.
     *
     * @var string
     */
    const DELETE = 'delete';
}