<?php

namespace Fndmiranda\DataMigrate\Traits;

use Fndmiranda\DataMigrate\Relations\StatusMany;
use Fndmiranda\DataMigrate\Relations\StatusOne;

trait HasRelationships
{
    use StatusMany, StatusOne;
}