<?php

namespace Fndmiranda\DataMigrate;

use Fndmiranda\DataMigrate\Traits\HasMigrate;
use Fndmiranda\DataMigrate\Traits\HasStatus;

class DataMigrate
{
    use HasStatus, HasMigrate;

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

    /**
     * Constant representing a not found status.
     *
     * @var string
     */
    const NOT_FOUND = 'not_found';

    /**
     * Constant representing the relation many-to-many.
     *
     * @var string
     */
    const BELONGS_TO_MANY = 'belongsToMany';

    /**
     * Constant representing the relation one-to-one.
     *
     * @var string
     */
    const HAS_ONE = 'hasOne';

    /**
     * Constant representing the relation one-to-many.
     *
     * @var string
     */
    const HAS_MANY = 'hasMany';

    /**
     * Constant representing the relation one-to-many inverse.
     *
     * @var string
     */
    const BELONGS_TO = 'belongsTo';
}