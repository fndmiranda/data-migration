<?php

namespace Fndmiranda\DataMigration\Traits;

use Fndmiranda\DataMigration\Contracts\DataMigration as ContractDataMigration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Fndmiranda\DataMigration\DataMigration;

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
     * The data of the data migration.
     *
     * @var Collection
     */
    private $data;

    /**
     * The options of the data migration.
     *
     * @var Collection
     */
    private $options;

    /**
     * Show the status of each data.
     *
     * @param ContractDataMigration $dataMigrate
     * @return Collection
     */
    public function status($dataMigrate)
    {
        $dataMigrate = $dataMigrate instanceof ContractDataMigration ? $dataMigrate : app($dataMigrate);
        $this->model = app($dataMigrate->model());
        $this->options = $dataMigrate->options() instanceof Collection ? $dataMigrate->options() : Collection::make($dataMigrate->options());
        $relations = Arr::get($this->options, 'relations', []);
        $data = $dataMigrate->data() instanceof Collection ? $dataMigrate->data() : Collection::make($dataMigrate->data());
        $this->data = $data->unique($this->options['identifier']);

        foreach ($this->data as $key => $item) {
            if (!(bool) $this->model->where($this->options['identifier'], '=', $item[$this->options['identifier']])->count()) {
                $this->data->put($key, ['data' => $item, 'status' => DataMigration::CREATE]);
            } else {
                $keys = array_keys($item);
                $clauses = Arr::where($keys, function ($value) use ($relations) {
                    return $value != $this->options['identifier'] && !in_array($value, array_pluck($relations, 'relation'));
                });

                $update = (bool) $this->model->where(function ($query) use ($clauses, $item) {
                    foreach (array_values($clauses) as $key => $clause) {
                        if (!$key) {
                            $query->where($clause, '!=', $item[$clause]);
                        } else {
                            $query->orWhere($clause, '!=', $item[$clause]);
                        }
                    }
                })->where($this->options['identifier'], '=', $item[$this->options['identifier']])->count();

                if ($update) {
                    $this->data->put($key, ['data' => $item, 'status' => DataMigration::UPDATE]);
                } else {
                    $this->data->put($key, ['data' => $item, 'status' => DataMigration::OK]);
                }
            }
        }

        $identifiers = $this->data->map(function ($item) {
            return $item['data'][$this->options['identifier']];
        });

        $removes = $this->model->whereNotIn($this->options['identifier'], $identifiers)->get();

        foreach ($removes as $remove) {
            $this->data->push(['data' => $remove->toArray(), 'status' => DataMigration::DELETE]);
        }

        $this->withRelationsStatus();

        return $this->data;
    }

    /**
     * Set the relations for the data migrate.
     *
     * @return $this
     */
    private function withRelationsStatus()
    {
        $relations = Arr::get($this->options, 'relations', []);

        foreach ($this->data as $key => $item) {
            foreach ($relations as $relation) {
                if (Arr::has($item['data'], $relation['relation'])) {
                    switch ($relation['type']) {
                        case DataMigration::BELONGS_TO_MANY:
                            $item = $this->statusMany($item, $relation['relation']);
                            break;
                        case DataMigration::BELONGS_TO:
                            $item = $this->statusOne($item, $relation['relation']);
                            break;
                    }
                }
            }

            $this->data->put($key, $item);
        }

        return $this;
    }
}