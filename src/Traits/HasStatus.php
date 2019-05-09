<?php

namespace Fndmiranda\DataMigrate\Traits;

use Fndmiranda\DataMigrate\Migrate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait HasStatus
{
    /**
     * Show the status of each data.
     *
     * @param Model|string $model
     * @param array|Collection $data
     * @param array|Collection $options
     * @return Collection
     */
    public function status($model, $data, $options = [])
    {
        $model = $model instanceof Model ? $model : app($model);
        $data = $data instanceof Collection ? $data : Collection::make($data);
        $options = $options instanceof Collection ? $options : Collection::make($options);
        $collection = collect();

        foreach ($data->unique('name') as $item) {
            if (!(bool) $model->where($options['identifier'], '=', $item[$options['identifier']])->count()) {
                $collection->push(['data' => $item, 'status' => Migrate::CREATE]);
            } else {
                $keys = array_keys($item);
                $clauses = Arr::where($keys, function ($value) use ($options) {
                    return $value != $options['identifier'];
                });

                $update = (bool) $model->where(function ($query) use ($clauses, $item) {
                    foreach (array_values($clauses) as $key => $clause) {
                        if (!$key) {
                            $query->where($clause, '!=', $item[$clause]);
                        } else {
                            $query->orWhere($clause, '!=', $item[$clause]);
                        }
                    }
                })->where($options['identifier'], '=', $item[$options['identifier']])->count();

                if ($update) {
                    $collection->push(['data' => $item, 'status' => Migrate::UPDATE]);
                } else {
                    $collection->push(['data' => $item, 'status' => Migrate::OK]);
                }
            }
        }

        $identifiers = $collection->map(function ($item) {
            return $item['data']['name'];
        });

        $removes = $model->whereNotIn($options['identifier'], $identifiers)->get();

        foreach ($removes as $remove) {
            $collection->push(['data' => $remove->toArray(), 'status' => Migrate::DELETE]);
        }

        $collection->dump();

        return $collection;
    }
}