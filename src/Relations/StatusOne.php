<?php

namespace Fndmiranda\DataMigration\Relations;

use Illuminate\Support\Arr;
use Fndmiranda\DataMigration\DataMigration;
use Illuminate\Support\Collection;

trait StatusOne
{
    /**
     * Set the status of data migration for the relation with one.
     *
     * @param array $values
     * @param string $relation
     * @return array
     */
    public function statusOne($values, $relation)
    {
        $relations = Arr::get($this->options, 'relations', []);
        $relation = Arr::first($relations, function ($value) use ($relation) {
            return $value['relation'] == $relation;
        });

        $relationModel = $this->model->{$relation['relation']}()->getModel();
        $relationData = $relationModel->where($relation['identifier'], '=', $values['data'][$relation['relation']][$relation['identifier']])->first();

        $inRemoves = false;
        if ($this->model->getTable() == $relationModel->getTable()) {
            if ($this->options['identifier'] == $relation['identifier']) {
                $inRemoves = (bool) $this->data->filter(function ($value) use ($values, $relation) {
                    return $value['data'][$relation['identifier']] ==
                        $values['data'][$relation['relation']][$relation['identifier']] &&
                        $value['status'] == DataMigration::DELETE;
                })->count();
            }
        }

        if ($relationData && !$inRemoves) {
            $ref = $this->model->{$relation['relation']}();

            $ownerKeyName = (float) app()->version() >= 5.8 ? $ref->getOwnerKeyName() : $ref->getOwnerKey();
            $foreignKeyName = (float) app()->version() >= 5.8 ? $ref->getForeignKeyName() : $ref->getForeignKey();

            $values['data'][$foreignKeyName] = $relationData->{$ownerKeyName};
            $status = DataMigration::OK;

            if ($values['status'] == DataMigration::OK) {
                $parent = $this->model->where($this->options['identifier'], '=', $values['data'][$this->options['identifier']])->first();
                if ($parent->{$foreignKeyName} != $relationData->{$ownerKeyName}) {
                    $relationsData = Arr::only($values, Arr::pluck($relations, 'relation'));
                    $values['data'] = array_merge($parent->toArray(), $values['data'], $relationsData);
                    $values['status'] = DataMigration::UPDATE;
                }
            }
        } else {
            $status = DataMigration::NOT_FOUND;
        }

        $values['data'][$relation['relation']] = [
            'data' => $values['data'][$relation['relation']],
            'status' => $status,
        ];

        return $values;
    }
}
