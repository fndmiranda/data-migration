<?php

namespace Fndmiranda\DataMigration\Traits;

use Fndmiranda\DataMigration\Contracts\DataMigration as ContractDataMigration;
use Fndmiranda\DataMigration\Relations\StatusOne;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Fndmiranda\DataMigration\DataMigration;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Helper\ProgressBar;

trait HasStatus
{
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
     * @param ProgressBar $progressBar
     * @return Collection
     */
    public function status($dataMigrate, $progressBar = null, $output = null)
    {
        $dataMigrate = $dataMigrate instanceof ContractDataMigration ? $dataMigrate : app($dataMigrate);
        $this->model = app($dataMigrate->model());
        $this->options = $dataMigrate->options() instanceof Collection ? $dataMigrate->options() : Collection::make($dataMigrate->options());
        $relations = Arr::get($this->options, 'relations', []);
        $data = $dataMigrate->data() instanceof Collection ? $dataMigrate->data() : Collection::make($dataMigrate->data());
        $this->data = $data->unique($this->options['identifier']);

        $identifiers = $this->data->map(function ($item) {
            return $item[$this->options['identifier']];
        });

        $removes = $this->model->whereNotIn($this->options['identifier'], $identifiers)->get();

        if ($progressBar) {
            $progressBar->setMaxSteps(count($this->data) + count($removes));
        }

        foreach ($this->data as $key => $item) {
            if (!(bool) $this->model->where($this->options['identifier'], '=', $item[$this->options['identifier']])->count()) {
                $this->data->put($key, ['data' => $item, 'status' => DataMigration::CREATE]);
            } else {
                $keys = array_keys($item);
                $clauses = Arr::where($keys, function ($value) use ($relations) {
                    return $value != $this->options['identifier'] && !in_array($value, array_pluck($relations, 'relation'));
                });

                $update = $this->model->where(function ($query) use ($clauses, $item) {
                    foreach (array_values($clauses) as $key => $clause) {
                        if (!$key) {
                            $query->where($clause, '!=', self::normalizeData($item[$clause]));
                        } else {
                            $query->orWhere($clause, '!=', self::normalizeData($item[$clause]));
                        }
                    }
                })->where($this->options['identifier'], '=', self::normalizeData($item[$this->options['identifier']]))->first();

                if ($update) {
                    $relationsData = Arr::only($item, Arr::pluck($relations, 'relation'));
                    $this->data->put($key, [
                        'data' => array_merge($update->toArray(), $item, $relationsData),
                        'status' => DataMigration::UPDATE,
                    ]);
                } else {
                    $this->data->put($key, ['data' => $item, 'status' => DataMigration::OK]);
                }
            }

            if ($progressBar) {
                $progressBar->advance();
            }
        }

        if ($progressBar) {
            $progressBar->finish();
        }

        if ($output) {
            $output->newLine();
            $output->writeln('Calculating relationships');
        }

        foreach ($removes as $remove) {
            $this->data->push(['data' => $remove->toArray(), 'status' => DataMigration::DELETE]);
        }

        $this->withRelationsStatus($progressBar);

        return $this->data;
    }

    /**
     * Set the relations for the data migrate.
     *
     * @return $this
     */
    private function withRelationsStatus($progressBar = null)
    {
        $relations = Arr::get($this->options, 'relations', []);

        if ($progressBar) {
            $progressBar->setMaxSteps(count($this->data));
            $progressBar->start();
        }

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

            if ($progressBar) {
                $progressBar->advance();
            }
        }
        if ($progressBar) {
            $progressBar->finish();
        }

        return $this;
    }

    /**
     * Normalize data to update.
     *
     * @param $data
     * @return boolean|string
     */
    private function normalizeData($data)
    {
        if (is_array($data) || is_object($data)) {
            return json_encode($data);
        } else {
            return $data;
        }
    }
}
