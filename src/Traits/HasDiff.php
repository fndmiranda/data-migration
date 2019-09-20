<?php

namespace Fndmiranda\DataMigration\Traits;

use Fndmiranda\DataMigration\Contracts\DataMigration as ContractDataMigration;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Fndmiranda\DataMigration\DataMigration;
use Symfony\Component\Console\Helper\ProgressBar;

trait HasDiff
{
    /**
     * Show changes to the data migrations.
     *
     * @param ContractDataMigration $dataMigrate
     * @param ProgressBar $progressBar
     * @return Collection
     */
    public function diff($dataMigrate, $progressBar = null, $output = null)
    {
        $dataMigrate = $dataMigrate instanceof ContractDataMigration ? $dataMigrate : app($dataMigrate);
        $status = $this->status($dataMigrate, $progressBar, $output)->toArray();
        $options = $dataMigrate->options() instanceof Collection ? $dataMigrate->options() : Collection::make($dataMigrate->options());
        $relations = Arr::get($options, 'relations', []);
        $status_no_changes = [DataMigration::OK, DataMigration::NOT_FOUND];

        foreach ($status as $key => $item) {
            $hasChange = false;
            foreach ($relations as $relation) {
                if (Arr::has($item['data'], $relation['relation'])) {
                    switch ($relation['type']) {
                        case DataMigration::BELONGS_TO_MANY:
                            foreach ($item['data'][$relation['relation']] as $k => $element) {
                                if (in_array($element['status'], $status_no_changes)) {
                                    $dot = implode('.', [$key, 'data', $relation['relation'], $k]);
                                    Arr::forget($status, $dot);
                                } else {
                                    $hasChange = true;
                                }
                            }
                            break;
                        case DataMigration::BELONGS_TO:
                            if (in_array($item['data'][$relation['relation']]['status'], $status_no_changes)) {
                                $dot = implode('.', [$key, 'data', $relation['relation']]);
                                Arr::forget($status, $dot);
                            } else {
                                $hasChange = true;
                            }
                            break;
                    }
                }
            }

            if (in_array($item['status'], [DataMigration::OK, DataMigration::NOT_FOUND]) && !$hasChange) {
                Arr::forget($status, $key);
            }
        }

        return collect($status);
    }
}
