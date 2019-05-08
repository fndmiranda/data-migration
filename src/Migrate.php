<?php

namespace Fndmiranda\DataMigrate;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Migrate
{
    /**
     * @param Model|string $model
     * @param array|Collection $data
     * @param array|Collection $options
     */
    public function status($model, $data, $options = [])
    {
        $data = $data instanceof Collection ? $data : Collection::make($data);
        $options = $options instanceof Collection ? $options : Collection::make($options);

        dump($options);
    }
}