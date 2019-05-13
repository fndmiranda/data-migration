<?php

namespace Fndmiranda\DataMigrate\Relations;

use Illuminate\Support\Arr;
use Fndmiranda\DataMigrate\DataMigrate;
use Illuminate\Support\Collection;

trait StatusOne
{
    /**
     * Set the status of data migration for the relation with one.
     *
     * @param array $values
     * @param array|Collection $options
     * @param string $relation
     * @return array
     */
    public function statusOne($values, $options, $relation)
    {
        $options = $options instanceof Collection ? $options : Collection::make($options);
        $relations = Arr::get($options, 'relations', []);
        $relation = Arr::first($relations, function ($value) use ($relation) {
            return $value['relation'] == $relation;
        });

        $relationModel = $this->model->{$relation['relation']}()->getModel();
        $relationData = $relationModel->where($relation['identifier'], '=', $values['data'][$relation['relation']][$relation['identifier']])->first();

        if ($relationData) {
            $ownerKey = $this->model->{$relation['relation']}()->getOwnerKey();
            $foreignKey = $this->model->{$relation['relation']}()->getForeignKey();
            $values['data'][$foreignKey] = $relationData->{$ownerKey};
            $status = DataMigrate::OK;

            if ($values['status'] == DataMigrate::OK) {
                $parent = $this->model->where($options['identifier'], '=', $values['data'][$options['identifier']])->first();
                if ($parent->{$foreignKey} != $relationData->{$ownerKey}) {
                    $values['status'] = DataMigrate::UPDATE;
                }
            }
        } else {
            $status = DataMigrate::NOT_FOUND;
        }

        $values['data'][$relation['relation']] = [
            'data' => $values['data'][$relation['relation']],
            'status' => $status,
        ];

        return $values;
    }
}