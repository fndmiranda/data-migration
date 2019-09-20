<?php

namespace Fndmiranda\DataMigration\Traits;

use Fndmiranda\DataMigration\Contracts\DataMigration as ContractDataMigration;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Fndmiranda\DataMigration\DataMigration;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\ProgressBar;

trait HasMigrate
{
    /**
     * Run the data migrations.
     *
     * @param ContractDataMigration $dataMigrate
     * @param ProgressBar $progressBar
     * @return Collection
     */
    public function migrate($dataMigrate, $progressBar = null, $output = null)
    {
        $dataMigrate = $dataMigrate instanceof ContractDataMigration ? $dataMigrate : app($dataMigrate);
        /* @var $status Collection */
        $status = $this->status($dataMigrate, $progressBar, $output);
        $options = $dataMigrate->options() instanceof Collection ? $dataMigrate->options() : Collection::make($dataMigrate->options());
        $relations = Arr::get($options, 'relations', []);

        DB::transaction(function () use ($dataMigrate, $status, $options, $relations) {
            foreach ($status as $key => $item) {
                if ($item['status'] == DataMigration::CREATE) {
                    $relationsData = Arr::only($item['data'], Arr::pluck($relations, 'relation'));
                    $entity = $this->model->create(Arr::except($item['data'], Arr::pluck($relations, 'relation')));
                    $status->put($key, [
                        'data' => array_merge($entity->toArray(), $relationsData),
                        'status' => DataMigration::CREATE,
                    ]);
                } else {
                    if (!$this->hasRelationCreate($item, $relations)) {
                        $status->forget($key);
                    }
                }
            }

            foreach ($status as $key => $item) {
                foreach ($relations as $relation) {
                    if (Arr::has($item['data'], $relation['relation'])) {
                        switch ($relation['type']) {
                            case DataMigration::BELONGS_TO_MANY:
                                $data = $this->migrateMany($item, $relation['relation']);
                                $item['data'][$relation['relation']] = $data['data'][$relation['relation']];
                                $status->put($key, $item);
                                break;
                        }
                    }
                }
            }
        });

        return $status;
    }

    /**
     * Check if has an relation with type create in item.
     *
     * @param $values
     * @param $relations
     * @return bool
     */
    private function hasRelationCreate($values, $relations)
    {
        foreach ($relations as $relation) {
            if (Arr::has($values['data'], $relation['relation'])) {
                if ($relation['type'] == DataMigration::BELONGS_TO_MANY) {
                    $creates = Arr::where($values['data'][$relation['relation']], function ($value) {
                        return $value['status'] == DataMigration::CREATE;
                    });
                    if ((bool) count($creates)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
