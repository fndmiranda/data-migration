<?php

namespace Fndmiranda\DataMigration\Traits;

use Fndmiranda\DataMigration\Relations\MigrateMany;
use Fndmiranda\DataMigration\Relations\StatusMany;
use Fndmiranda\DataMigration\Relations\StatusOne;

trait HasRelationships
{
    use StatusMany, StatusOne, MigrateMany;
}