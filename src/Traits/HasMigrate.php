<?php

namespace Fndmiranda\DataMigrate\Traits;

use Fndmiranda\DataMigrate\Contracts\DataMigrate as ContractDataMigrate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Fndmiranda\DataMigrate\DataMigrate;
use Illuminate\Support\Facades\DB;

trait HasMigrate
{
    use HasRelationships;

    /**
     * The model associated with the data migrate.
     *
     * @var Model
     */
    private $model;

    /**
     * Run the data migrations.
     *
     * @param ContractDataMigrate $dataMigrate
     * @return Collection
     */
    public function migrate($dataMigrate)
    {
        $dataMigrate = $dataMigrate instanceof ContractDataMigrate ? $dataMigrate : app($dataMigrate);
        $collection = collect();

        DB::transaction(function () use ($dataMigrate, $collection) {
            dump($this->status($dataMigrate));
            $creates = $this->status($dataMigrate)->filter(function ($value) {
                return $value['status'] == DataMigrate::CREATE;
            });

            foreach ($creates as $item) {
//                dump($item['data']);
//                $entity = $this->model->create($item['data']);
//                $collection->push($entity->toArray());
            }
        });

        return $collection;
    }
}