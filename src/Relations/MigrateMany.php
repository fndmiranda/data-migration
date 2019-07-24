<?php

namespace Fndmiranda\DataMigration\Relations;

use Illuminate\Support\Arr;
use Fndmiranda\DataMigration\DataMigration;

trait MigrateMany
{
    /**
     * Migrate of data migration for the relation with many.
     *
     * @param array $values
     * @param string $relation
     * @return array
     */
    public function migrateMany($values, $relation)
    {
        $relations = Arr::get($this->options, 'relations', []);
        $relation = Arr::first($relations, function ($value) use ($relation) {
            return $value['relation'] == $relation;
        });
        $relationModel = $this->model->{$relation['relation']}()->getModel();

        foreach ($values['data'][$relation['relation']] as $key => $item) {
            if ($item['status'] == DataMigration::CREATE) {
                $data = $relationModel->where($relation['identifier'], '=', $item['data'][$relation['identifier']])->first();
//                dump('kkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkk'.$data->id);

                $this->model
                    ->where($this->options['identifier'], '=', $values['data'][$this->options['identifier']])
                    ->first()
                    ->{$relation['relation']}()
                    ->attach($data->id, Arr::except($item['data'], [$relation['identifier']]));

                $values['data'][$relation['relation']][$key]['data'] = array_merge(
                    $data->toArray(),
                    $item['data']
                );
            } else {
                $dot = implode('.', ['data', $relation['relation'], $key]);
                Arr::forget($values, $dot);
            }
        }

        return $values;
    }
}
