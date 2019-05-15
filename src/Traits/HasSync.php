<?php

namespace Fndmiranda\DataMigration\Traits;

use Fndmiranda\DataMigration\Contracts\DataMigration as ContractDataMigration;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Fndmiranda\DataMigration\DataMigration;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\ProgressBar;

trait HasSync
{
    use HasRelationships;

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
        $status = $this->diff($dataMigrate, $progressBar)->toArray();
        $options = $dataMigrate->options() instanceof Collection ? $dataMigrate->options() : Collection::make($dataMigrate->options());
        $relations = Arr::get($options, 'relations', []);

        DB::transaction(function () use ($dataMigrate, $status, $options, $relations) {
            foreach ($status as $key => $item) {
                dump($item);
            }
        });

        return collect($status);
    }
}