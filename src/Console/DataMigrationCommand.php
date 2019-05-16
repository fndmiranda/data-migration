<?php

namespace Fndmiranda\DataMigration\Console;

use Fndmiranda\DataMigration\Contracts\DataMigration as ContractDataMigration;
use Fndmiranda\DataMigration\DataMigration;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

abstract class DataMigrationCommand extends Command
{
    /**
     * The status column mapping formatted.
     *
     * @var array
     */
    protected $status = [
        DataMigration::OK => '<fg=white>OK</fg=white>',
        DataMigration::CREATE => '<fg=green>Create</fg=green>',
        DataMigration::UPDATE => '<fg=yellow>Update</fg=yellow>',
        DataMigration::DELETE => '<fg=red>Delete</fg=red>',
        DataMigration::NOT_FOUND => '<fg=blue>Not found</fg=blue>',
    ];

    /**
     * The migration instance.
     *
     * @var ContractDataMigration
     */
    protected $migration;

    /**
     * The data migration rows.
     *
     * @var array
     */
    protected $rows = [];

    /**
     * Relationship tables.
     *
     * @var array
     */
    protected $relationships = [];

    /**
     * Get a data migration instance.
     *
     * @return ContractDataMigration
     */
    protected function getMigration()
    {
        return $this->migration;
    }

    /**
     * Set a data migration instance.
     *
     * @param string $migration
     * @return $this
     */
    protected function setMigration($migration)
    {
        $this->migration = $this->laravel->make($migration);

        return $this;
    }

    protected function prepare($data, $options)
    {
        $relations = Arr::get($options, 'relations', []);

        foreach ($data as $key => $item) {
            $this->rows[] = $this->getRow($item, $options['show']);

            foreach ($relations as $relation) {
                if (Arr::has($item['data'], $relation['relation'])) {
                    switch ($relation['type']) {
                        case DataMigration::BELONGS_TO_MANY:
                            if (!Arr::has($this->relationships, $relation['relation'])) {
                                Arr::set($this->relationships, $relation['relation'], ['rows' => []]);
                            }

                            $this->relationships[$relation['relation']]['headers'] = $this->getHeaders($relation['show']);

                            foreach ($item['data'][$relation['relation']] as $element) {
                                $this->relationships[$relation['relation']]['rows'][] = $this->getRow($element, $relation['show']);
                            }
                            break;
                        case DataMigration::BELONGS_TO:
                            if (!Arr::has($this->relationships, $relation['relation'])) {
                                Arr::set($this->relationships, $relation['relation'], ['rows' => []]);
                            }

                            $this->relationships[$relation['relation']]['headers'] = $this->getHeaders($relation['show']);
                            $this->relationships[$relation['relation']]['rows'][] = $this->getRow($item['data'][$relation['relation']], $relation['show']);
                            break;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Get the relationships.
     *
     * @return array
     */
    protected function getRelationships()
    {
        return $this->relationships;
    }

    /**
     * Get the rows.
     *
     * @return array
     */
    protected function getRows()
    {
        return $this->rows;
    }

    /**
     * Get the row.
     *
     * @param array $value
     * @param array $headers
     * @return array
     */
    protected function getRow($value, $headers)
    {
        $columns = [];
        foreach ($headers as $column) {
            $columns[$column] = Arr::get($value['data'], $column);
        }
        $columns['status'] = $this->status[$value['status']];

        return $columns;
    }

    /**
     * Get the headers.
     *
     * @param array $values
     * @return mixed
     */
    protected function getHeaders($values)
    {
        $headers = $values;
        $headers[] = 'status';

        return $headers;
    }
}
