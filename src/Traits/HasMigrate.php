<?php

namespace Fndmiranda\DataMigration\Traits;

use Fndmiranda\DataMigration\Contracts\DataMigration as ContractDataMigration;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Fndmiranda\DataMigration\DataMigration;
use Illuminate\Support\Facades\DB;

trait HasMigrate
{
    use HasRelationships;

    /**
     * Run the data migrations.
     *
     * @param ContractDataMigration $dataMigrate
     * @return Collection
     */
    public function migrate($dataMigrate)
    {
        $dataMigrate = $dataMigrate instanceof ContractDataMigration ? $dataMigrate : app($dataMigrate);
        /* @var $status Collection */
        $status = $this->status($dataMigrate);
        $options = $dataMigrate->options() instanceof Collection ? $dataMigrate->options() : Collection::make($dataMigrate->options());
        $relations = Arr::get($options, 'relations', []);

        DB::transaction(function () use ($dataMigrate, $status, $options, $relations) {
            foreach ($status as $key => $item) {
                if ($item['status'] == DataMigration::CREATE) {
                    $relationsData = Arr::only($item['data'], Arr::pluck($relations, 'relation'));
                    $entity = $this->model->create($item['data']);
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