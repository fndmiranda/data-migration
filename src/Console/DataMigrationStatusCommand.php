<?php

namespace Fndmiranda\DataMigration\Console;

use Fndmiranda\DataMigration\Contracts\DataMigration as ContractDataMigration;
use Fndmiranda\DataMigration\Facades\DataMigration as FacadeDataMigration;
use Fndmiranda\DataMigration\DataMigration;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class DataMigrationStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data-migration:status {migration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the status of each data';

    /**
     * The status column mapping formatted.
     *
     * @var array
     */
    protected $status = [
        DataMigration::OK => '<fg=white>OK</fg=white>',
        DataMigration::CREATE => '<fg=green>Delete</fg=green>',
        DataMigration::UPDATE => '<fg=yellow>Update</fg=yellow>',
        DataMigration::DELETE => '<fg=red>Delete</fg=red>',
    ];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->table($this->getHeaders(), $this->getData());
    }

    /**
     * Get a data migration instance from the container.
     *
     * @return ContractDataMigration
     */
    protected function getMigration()
    {
        $class = $this->laravel->make($this->argument('migration'));

        return $class;
    }

    /**
     * Get the data.
     *
     * @return array
     */
    protected function getData()
    {
        $migration = $this->getMigration();
        $status = FacadeDataMigration::status($migration);

        $data = array_map(function ($value) use ($migration) {
            $columns = [];
            foreach ($migration->options()['show'] as $column) {
                $columns[$column] = Arr::get($value['data'], $column);
            }
            $columns['status'] = $this->status[$value['status']];

            return $columns;
        }, $status->toArray());

        return $data;
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
