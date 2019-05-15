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

    /**
     * Get the rows.
     *
     * @param array $data
     * @param array $options
     * @return array
     */
    protected function getRows($data, $options)
    {
        $rows = array_map(function ($value) use ($options) {
            $columns = [];
            foreach ($options['show'] as $column) {
                $columns[$column] = Arr::get($value['data'], $column);
            }
            $columns['status'] = $this->status[$value['status']];

            return $columns;
        }, $data);

        return $rows;
    }

    /**
     * Get the headers.
     *
     * @return mixed
     */
    protected function getHeaders()
    {
        $migration = $this->getMigration();
        $headers = $migration->options()['show'];
        $headers[] = 'status';

        return $headers;
    }
}