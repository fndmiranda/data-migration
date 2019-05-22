<?php

namespace Fndmiranda\DataMigration\Traits;

trait HasMethods
{
    use HasMigrate, HasDiff, HasSync, HasStatus;
}
