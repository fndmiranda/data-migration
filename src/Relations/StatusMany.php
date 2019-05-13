<?php

namespace Fndmiranda\DataMigration\Relations;

use Illuminate\Support\Arr;
use Fndmiranda\DataMigration\DataMigration;
use Illuminate\Support\Collection;

trait StatusMany
{
    /**
     * Set the status of data migration for the relation with many.
     *
     * @param array $values
     * @param array|Collection $options
     * @param string $relation
     * @return array
     */
    public function statusMany($values, $options, $relation)
    {
        $options = $options instanceof Collection ? $options : Collection::make($options);
        $relations = Arr::get($options, 'relations', []);
        $relation = Arr::first($relations, function ($value) use ($relation) {
            return $value['relation'] == $relation;
        });

        switch ($values['status']) {
            case DataMigration::CREATE:
                foreach ($values['data'][$relation['relation']] as $key => $item) {
                    $hasItem = $this->hasRelationItem($item, $relation);
                    $status = $hasItem ? DataMigration::CREATE : DataMigration::NOT_FOUND;

                    $values['data'][$relation['relation']][$key] = [
                        'data' => $item,
                        'status' => $status,
                    ];
                }
                break;
            case DataMigration::OK:
            case DataMigration::UPDATE:
                foreach ($values['data'][$relation['relation']] as $key => $item) {
                    $hasItem = $this->hasRelationItem($item, $relation);

                    if ($hasItem) {
                        $count = (bool)$this->model
                            ->where($options['identifier'], '=', $values['data'][$options['identifier']])
                            ->first()
                            ->{$relation['relation']}()
                            ->where($relation['identifier'], '=', $item[$relation['identifier']])
                            ->count();

                        if (!$count) {
                            $values['data'][$relation['relation']][$key] = [
                                'data' => $item,
                                'status' => DataMigration::CREATE,
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
                                $values['data'][$relation['relation']][$key] = [
                                    'data' => $item,
                                    'status' => DataMigration::UPDATE,
                                ];
                            } else {
                                $values['data'][$relation['relation']][$key] = [
                                    'data' => $item,
                                    'status' => DataMigration::OK,
                                ];
                            }
                        }
                    } else {
                        $values['data'][$relation['relation']][$key] = [
                            'data' => $item,
                            'status' => DataMigration::NOT_FOUND,
                        ];
                    }
                }

                $identifiers = collect($values['data'][$relation['relation']])->map(function ($item) use ($relation) {
                    return $item['data'][$relation['identifier']];
                });

                $removes = $this->model
                    ->where($options['identifier'], '=', $values['data'][$options['identifier']])
                    ->first()
                    ->{$relation['relation']}()
                    ->whereNotIn($relation['identifier'], $identifiers)->get();

                foreach ($removes as $remove) {
                    $values['data'][$relation['relation']][] = ['data' => $remove->toArray(), 'status' => DataMigration::DELETE];
                }

                break;
        }

        return $values;
    }

    /**
     * Check if has item of the relation.
     *
     * @param $item $values
     * @param string $relation
     * @return boolean
     */
    private function hasRelationItem($item, $relation)
    {
        $relationModel = $this->model->{$relation['relation']}()->getModel();
        $relationData = $relationModel->where($relation['identifier'], '=', $item[$relation['identifier']])->first();
        return (bool) $relationData;
    }
}