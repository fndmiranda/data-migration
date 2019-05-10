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
                $modelRelation = $this->model->{$relation['relation']}()->getModel();

                $test = $modelRelation->where($relation['identifier'], '=', 'Brand test 1')->count();

                /* @var $t \Illuminate\Database\Eloquent\Relations\BelongsTo */
                $t = $this->model->{$relation['relation']}();

                dd([
                    '$t->getOwnerKey()' => $t->getOwnerKey(),
                    '$t->getForeignKey()' => $t->getForeignKey(),
                ]);

                $values['data'][$relation['relation']] = [
                    ['data' => $values['data'][$relation['relation']], 'status' => DataMigrate::CREATE]
                ];
                break;
            case DataMigrate::OK:
            case DataMigrate::UPDATE:
                $item = $values['data'][$relation['relation']];
                $count = (bool) $this->model
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

                break;
        }

        return $values;
    }
}