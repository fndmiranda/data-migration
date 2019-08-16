<?php

namespace Fndmiranda\DataMigration\Traits;

use Fndmiranda\DataMigration\Contracts\DataMigration as ContractDataMigration;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Fndmiranda\DataMigration\DataMigration;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\ProgressBar;

trait HasSync
{
    /**
     * Synchronization changes to the data migrations.
     *
     * @param ContractDataMigration $dataMigrate
     * @param ProgressBar $progressBar
     * @return Collection
     */
    public function sync($dataMigrate, $progressBar = null)
    {
        $dataMigrate = $dataMigrate instanceof ContractDataMigration ? $dataMigrate : app($dataMigrate);
        $status = $this->diff($dataMigrate, $progressBar);
        $options = $dataMigrate->options() instanceof Collection ? $dataMigrate->options() : Collection::make($dataMigrate->options());
        $relations = Arr::get($options, 'relations', []);
        $isSoftDeletes = in_array(SoftDeletes::class, class_uses($this->model));

        DB::transaction(function () use ($dataMigrate, $status, $options, $relations, $isSoftDeletes) {
            foreach ($status as $key => $item) {
                switch ($item['status']) {
                    case DataMigration::CREATE:
                        $relationsData = Arr::only($item['data'], Arr::pluck($relations, 'relation'));
                        $entity = $this->model->create(Arr::except($item['data'], Arr::pluck($relations, 'relation')));
                        $status->put($key, [
                            'data' => array_merge($entity->toArray(), $relationsData),
                            'status' => DataMigration::CREATE,
                        ]);
                        break;
                    case DataMigration::DELETE:
                        $instance = $this->model->find($item['data'][$this->model->getKeyName()]);
                        $instance->delete();

                        if ($isSoftDeletes) {
                            $relationsData = Arr::only($item['data'], Arr::pluck($relations, 'relation'));
                            $status->put($key, [
                                'data' => array_merge($instance->toArray(), $relationsData),
                                'status' => DataMigration::DELETE,
                            ]);
                        }
                        break;
                    case DataMigration::UPDATE:
                        $instance = $this->model->find($item['data'][$this->model->getKeyName()]);
                        $relationsData = Arr::only($item['data'], Arr::pluck($relations, 'relation'));

                        $keys = array_flip(array_keys($dataMigrate->data()[$key]));
                        $except = Arr::pluck($relations, 'relation');
                        $only = array_flip(Arr::except($keys, $except));

                        $instance->update(Arr::only($item['data'], $only));
                        $status->put($key, [
                            'data' => array_merge($instance->toArray(), $relationsData),
                            'status' => DataMigration::UPDATE,
                        ]);
                        break;
                }
            }

            foreach ($status as $key => $item) {
                foreach ($relations as $relation) {
                    if (Arr::has($item['data'], $relation['relation'])) {
                        switch ($relation['type']) {
                            case DataMigration::BELONGS_TO_MANY:
                                $data = $this->syncMany($item, $relation['relation']);
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
}
