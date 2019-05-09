<?php

namespace Fndmiranda\DataMigrate\Traits;

use Fndmiranda\DataMigrate\Contracts\DataMigrate as ContractDataMigrate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Fndmiranda\DataMigrate\DataMigrate;

trait HasStatus
{
    /**
     * The model associated with the data migrate.
     *
     * @var Model
     */
    private $model;

    /**
     * Show the status of each data.
     *
     * @param ContractDataMigrate $dataMigrate
     * @return Collection
     */
    public function status($dataMigrate)
    {
        $dataMigrate = $dataMigrate instanceof ContractDataMigrate ? $dataMigrate : app($dataMigrate);
        $this->model = app($dataMigrate->model());
        $data = $dataMigrate->data() instanceof Collection ? $dataMigrate->data() : Collection::make($dataMigrate->data());
        $options = $dataMigrate->options() instanceof Collection ? $dataMigrate->options() : Collection::make($dataMigrate->options());
        $collection = collect();
        $relations = data_get($options, 'relations', []);

        foreach ($data->unique('name') as $item) {
            if (!(bool) $this->model->where($options['identifier'], '=', $item[$options['identifier']])->count()) {
                $collection->push(['data' => $item, 'status' => DataMigrate::CREATE]);
            } else {
                $keys = array_keys($item);
                $clauses = Arr::where($keys, function ($value) use ($options, $relations) {
                    return $value != $options['identifier'] && !in_array($value, array_pluck($relations, 'relation'));
                });

                $update = (bool) $this->model->where(function ($query) use ($clauses, $item) {
                    foreach (array_values($clauses) as $key => $clause) {
                        if (!$key) {
                            $query->where($clause, '!=', $item[$clause]);
                        } else {
                            $query->orWhere($clause, '!=', $item[$clause]);
                        }
                    }
                })->where($options['identifier'], '=', $item[$options['identifier']])->count();

                if ($update) {
                    $collection->push(['data' => $item, 'status' => DataMigrate::UPDATE]);
                } else {
                    $collection->push(['data' => $item, 'status' => DataMigrate::OK]);
                }
            }
        }

        $identifiers = $collection->map(function ($item) {
            return $item['data']['name'];
        });

        $removes = $this->model->whereNotIn($options['identifier'], $identifiers)->get();

        foreach ($removes as $remove) {
            $collection->push(['data' => $remove->toArray(), 'status' => DataMigrate::DELETE]);
        }

        $this->relations($collection, $options);

        return $collection;
    }

    private function relations(Collection $collection, Collection $options)
    {
        $relations = data_get($options, 'relations', []);

        foreach ($collection as $item) {
            foreach ($relations as $relation) {
                if (Arr::has($item['data'], $relation['relation'])) {
                    $this->{$relation['type']}($item, $options, $relation['relation']);
                }
            }
        }
    }

    private function belongsToMany($values, $options, $relation)
    {
        dump($values);
        dump($options);
        dump($relation);
    }
}