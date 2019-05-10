<?php

namespace Fndmiranda\DataMigrate\Relations;

use Illuminate\Support\Arr;
use Fndmiranda\DataMigrate\DataMigrate;
use Illuminate\Support\Collection;

trait BelongsToMany
{
    /**
     * Set the data migration for the relation many-to-many.
     *
     * @param array $values
     * @param array|Collection $options
     * @param string $relation
     * @return array
     */
    public function belongsToMany($values, $options, $relation)
    {
        $options = $options instanceof Collection ? $options : Collection::make($options);
        $relations = data_get($options, 'relations', []);
        $relation = Arr::first($relations, function ($value) use ($relation) {
            return $value['relation'] == $relation;
        });

        switch ($values['status']) {
            case DataMigrate::CREATE:
                foreach ($values['data'][$relation['relation']] as $key => $item) {
                    $values['data'][$relation['relation']][$key] = [
                        'data' => $item,
                        'status' => DataMigrate::CREATE,
                    ];
                }
                break;
            case DataMigrate::OK:
            case DataMigrate::UPDATE:
                foreach ($values['data'][$relation['relation']] as $key => $item) {
                    $count = (bool) $this->model
                        ->where($options['identifier'], '=', $values['data'][$options['identifier']])
                        ->first()
                        ->{$relation['relation']}()
                        ->where($relation['identifier'], '=', $item[$relation['identifier']])
                        ->count();

                    if (!$count) {
                        $values['data'][$relation['relation']][$key] = [
                            'data' => $item,
                            'status' => DataMigrate::CREATE,
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
                                'status' => DataMigrate::UPDATE,
                            ];
                        } else {
                            $values['data'][$relation['relation']][$key] = [
                                'data' => $item,
                                'status' => DataMigrate::OK,
                            ];
                        }
                    }
                }
                break;
        }

        return $values;
    }
}