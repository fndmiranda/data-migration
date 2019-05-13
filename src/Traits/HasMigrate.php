<?php

namespace Fndmiranda\DataMigration\Traits;

use Fndmiranda\DataMigration\Contracts\DataMigration as ContractDataMigration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Fndmiranda\DataMigration\DataMigration;
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
     * @param ContractDataMigration $dataMigrate
     * @return Collection
     */
    public function migrate($dataMigrate)
    {
        $dataMigrate = $dataMigrate instanceof ContractDataMigration ? $dataMigrate : app($dataMigrate);
        $collection = collect();

        DB::transaction(function () use ($dataMigrate, $collection) {
            dump($this->status($dataMigrate));
            $creates = $this->status($dataMigrate)->filter(function ($value) {
                return $value['status'] == DataMigration::CREATE;
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