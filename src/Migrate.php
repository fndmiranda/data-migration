<?php

namespace Fndmiranda\DataMigrate;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class Migrate
{
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
                $collection->push(['data' => $item, 'status' => self::CREATE]);
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
                    $collection->push(['data' => $item, 'status' => self::UPDATE]);
                } else {
                    $collection->push(['data' => $item, 'status' => self::OK]);
                }
            }
        }

        $identifiers = $collection->map(function ($item) {
            return $item['data']['name'];
        });

        $removes = $model->whereNotIn($options['identifier'], $identifiers)->get();

        foreach ($removes as $remove) {
            $collection->push(['data' => $remove->toArray(), 'status' => self::DELETE]);
        }

        return $collection;
    }
}