<?php

namespace Fndmiranda\DataMigration\Console;

use Fndmiranda\DataMigration\Facades\DataMigration;
use Symfony\Component\Console\Helper\TableCell;

class DataMigrationStatusCommand extends DataMigrationCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data-migration:status
                            {migration? : The data migration to run}
                            {--path=* : Path to find data migrations}
                            {--tag=* : One or many tags that have data you want to migrate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the status of each data with the database';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->argument('migration')) {
            $this->status($this->argument('migration'));
        } else {
            $collection = $this->findMigrations($this->option('path'), $this->option('tag'));

            if ($collection->count()) {
                foreach ($collection as $class) {
                    $this->status($class->getName());
                }
            } else {
                $this->info('No data migration found.');
            }
        }
    }

    /**
     * Status of a data migration.
     *
     * @param string $migration
     */
    protected function status(string $migration)
    {
        $this->setMigration($migration);

        $this->getOutput()->writeln(sprintf('<comment>Calculating to %s:</comment>', $this->getMigration()->model()));
        $progressBar = $this->output->createProgressBar(count($this->getMigration()->data()));
        $progressBar->start();

        $data = DataMigration::status($this->getMigration(), $progressBar)->toArray();
        $options = $this->getMigration()->options();
        $this->prepare($data, $options);

        $progressBar->finish();
        $this->getOutput()->newLine();

        $rows = $this->prepareRows($this->getRows());
        $relationships = $this->getRelationships();

        if (!count($rows) && !count($relationships)) {
            $this->info('Nothing to do.');
        } else {
            $this->table($this->getHeaders($options['show']), $rows);

            foreach ($this->getRelationships() as $relationship => $data) {
                $headers = [
                    [new TableCell($relationship, ['colspan' => count($data['headers'])])],
                    $data['headers'],
                ];

                $this->table($headers, $data['rows']);
            }
        }
    }

    /**
     * Prepare rows to show status.
     *
     * @param $rows
     * @return \Illuminate\Support\Collection
     */
    private function prepareRows($rows)
    {
        $data = collect($rows)->map(function ($item, $key) {
            $row = [];
            foreach ($item as $column => $value) {
                $row[$column] = $this->prepareValue($value);
            }
            return $row;
        });

        return $data;
    }

    /**
     * Prepare value to show status.
     *
     * @param $value
     * @return string
     */
    private function prepareValue($value)
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return $value;
    }
}
