<?php

namespace Fndmiranda\DataMigration\Relations;

use Illuminate\Support\Arr;
use Fndmiranda\DataMigration\DataMigration;

trait SyncMany
{
    /**
     * Synchronization of data migration for the relation with many.
     *
     * @param array $values
     * @param string $relation
     * @return array
     */
    public function syncMany($values, $relation)
    {
        $relations = Arr::get($this->options, 'relations', []);
        $relation = Arr::first($relations, function ($value) use ($relation) {
            return $value['relation'] == $relation;
        });
        $relationModel = $this->model->{$relation['relation']}()->getModel();
        $relationKeyName = $this->model->{$relation['relation']}()->getModel()->getKeyName();

        $foreignPivotKeyName = $this->model->{$relation['relation']}()->getForeignPivotKeyName();
        $relatedPivotKeyName = $this->model->{$relation['relation']}()->getRelatedPivotKeyName();

//        $ownerKey = $this->model->{$relation['relation']}()->getOwnerKey();
//        $foreignKey = $this->model->{$relation['relation']}()->getForeignKey();

        foreach ($values['data'][$relation['relation']] as $key => $item) {
            switch ($item['status']) {
                case DataMigration::CREATE:
                    $data = $relationModel->where($relation['identifier'], '=', $item['data'][$relation['identifier']])->first();

                    $this->model
                        ->where($this->options['identifier'], '=', $values['data'][$this->options['identifier']])
                        ->first()
                        ->{$relation['relation']}()
                        ->attach($data->id, Arr::except($item['data'], [$relation['identifier']]));

                    $values['data'][$relation['relation']][$key]['data'] = array_merge(
                        $data->toArray(), $item['data']
                    );
                    break;
                case DataMigration::DELETE:
                    $this->model
                        ->where($this->options['identifier'], '=', $values['data'][$this->options['identifier']])
                        ->first()
                        ->{$relation['relation']}()
                        ->detach($item['data'][$relationKeyName]);
                    break;
                case DataMigration::UPDATE:
                    $keysPivot = array_keys(Arr::except($item['data']['pivot'], [$foreignPivotKeyName, $relatedPivotKeyName]));
                    $this->model
                        ->where($this->options['identifier'], '=', $values['data'][$this->options['identifier']])
                        ->first()
                        ->{$relation['relation']}()
                        ->updateExistingPivot($item['data'][$relationKeyName], Arr::only($item['data'], $keysPivot));

                    $entity = $this->model
                        ->where($this->options['identifier'], '=', $values['data'][$this->options['identifier']])
                        ->first()
                        ->{$relation['relation']}()
                        ->find($item['data'][$relationKeyName]);

                    $values['data'][$relation['relation']][$key]['data'] = array_merge(
                        $entity->toArray(),
                        Arr::only($item['data'], $keysPivot)
                    );
                    break;
            }
        }

        return $values;
    }
}