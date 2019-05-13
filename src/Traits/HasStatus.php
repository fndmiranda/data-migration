<?php

namespace Fndmiranda\DataMigrate\Traits;

use Fndmiranda\DataMigrate\Contracts\DataMigrate as ContractDataMigrate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Fndmiranda\DataMigrate\DataMigrate;

trait HasStatus
{
    use HasRelationships;

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
        $relations = Arr::get($options, 'relations', []);

        foreach ($data->unique($options['identifier']) as $item) {
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

        $identifiers = $collection->map(function ($item) use ($options) {
            return $item['data'][$options['identifier']];
        });

        $removes = $this->model->whereNotIn($options['identifier'], $identifiers)->get();

        foreach ($removes as $remove) {
            $collection->push(['data' => $remove->toArray(), 'status' => DataMigrate::DELETE]);
        }

        $collectionWithRelationsStatus = $this->withRelationsStatus($collection, $options);

//        $collectionWithRelationsStatus->dump();

        return $collectionWithRelationsStatus;
    }

    /**
     * Set the relations for the data migrate.
     *
     * @param Collection $dataMigrate
     * @param Collection $options
     * @return Collection
     */
    private function withRelationsStatus(Collection $dataMigrate, Collection $options)
    {
        $relations = Arr::get($options, 'relations', []);
        $dataMigrateWithRelationStatus = collect();

        foreach ($dataMigrate as $item) {
            foreach ($relations as $relation) {
                if (Arr::has($item['data'], $relation['relation'])) {
                    switch ($relation['type']) {
                        case DataMigrate::BELONGS_TO_MANY:
                            $item = $this->statusMany($item, $options, $relation['relation']);
                            break;
                        case DataMigrate::BELONGS_TO:
                            $item = $this->statusOne($item, $options, $relation['relation']);
                            break;
                    }
                }
            }

            $dataMigrateWithRelationStatus->push($item);
        }

        return $dataMigrateWithRelationStatus;
    }
}