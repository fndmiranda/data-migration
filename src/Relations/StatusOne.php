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
        $relations = data_get($options, 'relations', []);
        $relation = Arr::first($relations, function ($value) use ($relation) {
            return $value['relation'] == $relation;
        });

        switch ($values['status']) {
            case DataMigrate::CREATE:
                $status = DataMigrate::CREATE;

                if ($relation['type'] == DataMigrate::BELONGS_TO) {
                    $values = $this->belongsTo($values, $options, $relation);
                } else {
                    $values['data'][$relation['relation']] = [
                        ['data' => $values['data'][$relation['relation']], 'status' => $status]
                    ];
                }
                break;
            case DataMigrate::OK:
            case DataMigrate::UPDATE:
                $item = $values['data'][$relation['relation']];

                if ($relation['type'] == DataMigrate::BELONGS_TO) {
                    $values = $this->belongsTo($values, $options, $relation);
                } else {
                    $count = (bool)$this->model
                        ->where($options['identifier'], '=', $values['data'][$options['identifier']])
                        ->first()
                        ->{$relation['relation']}()
                        ->where($relation['identifier'], '=', $item[$relation['identifier']])
                        ->count();

                    if (!$count) {
                        $values['data'][$relation['relation']] = [
                            ['data' => $item, 'status' => DataMigrate::CREATE]
                        ];
                    } else {
                        $update = false;
                        $keys = array_keys($item);
                        $clauses = Arr::where($keys, function ($value) use ($relation) {
                            return $value != $relation['identifier'];
                        });

                        if (count($clauses)) {
                            $update = (bool)$this->model
                                ->where($options['identifier'], '=', $values['data'][$options['identifier']])
                                ->first()
                                ->{$relation['relation']}()
                                ->where($relation['identifier'], '=', $item[$relation['identifier']])
                                ->where(function ($query) use ($relation, $item) {
                                    $keys = array_keys($item);
                                    $clauses = Arr::where($keys, function ($value) use ($relation) {
                                        return $value != $relation['identifier'];
                                    });

                                    foreach (array_values($clauses) as $key => $clause) {
                                        if (!$key) {
                                            $query->where($clause, '!=', $item[$clause]);
                                        } else {
                                            $query->orWhere($clause, '!=', $item[$clause]);
                                        }
                                    }
                                })
                                ->count();
                        }

                        if ($update) {
                            $values['data'][$relation['relation']] = [
                                ['data' => $item, 'status' => DataMigrate::UPDATE]
                            ];
                        } else {
                            $values['data'][$relation['relation']] = [
                                ['data' => $item, 'status' => DataMigrate::OK]
                            ];
                        }
                    }
                }

                if ($relation['type'] != DataMigrate::BELONGS_TO) {
                    $identifiers = collect([$item])->map(function ($item) use ($relation) {
                        return $item[$relation['identifier']];
                    });

                    $removes = $this->model
                        ->where($options['identifier'], '=', $values['data'][$options['identifier']])
                        ->first()
                        ->{$relation['relation']}()
                        ->whereNotIn($relation['identifier'], $identifiers)->get();

                    foreach ($removes as $remove) {
                        $values['data'][$relation['relation']][] = ['data' => $remove->toArray(), 'status' => DataMigrate::DELETE];
                    }
                }

                break;
        }

        return $values;
    }

    /**
     * Prepare data with the belongsTo relation.
     *
     * @param array $values
     * @param array|Collection $options
     * @param string $relation
     * @return mixed
     */
    private function belongsTo($values, $options, $relation)
    {
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
            ['data' => $values['data'][$relation['relation']], 'status' => $status]
        ];

        return $values;
    }
}